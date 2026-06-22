<?php

namespace App\Http\Controllers\auth;

use App\Events\SendMessageEvent;
use App\Events\SendVerificationEmailEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\auth\LoginRequest;
use App\Http\Requests\auth\RegisterRequest;
use App\Http\Requests\auth\SignupRequest;
use App\Http\Resources\UserResource;
use App\Models\Acteurs\Client;
use App\Models\Acteurs\Professionel;
use App\Models\User;
use App\Traits\ApiResponses;
use App\Traits\CloudflareUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    use ApiResponses, CloudflareUpload;

    /** This method is used to signup a user of role super_admin, admin and user */
    public function signup(SignupRequest $request)
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);
        $data['is_phone_verified'] = true;
        $data['is_email_verified'] = true;
        $data['is_approved'] = true;
        $data['is_active'] = true;

        $user = User::create($data);

        $token = $user->createToken('auth')->plainTextToken;

        return $this->successResponseWithToken($user, $token, "Utilisateur inscrit avec succès.");
    }

    // This methode is used to signup a user of role professionel or client
    public function register(RegisterRequest $request)
    {
        $uploadedDocument = null;

        try {
            $data = $request->validated();
            $data['role'] = $this->normalizeRole($data['role']);
            $data['password'] = Hash::make($data['password']);

            $user = DB::transaction(function () use ($data, &$uploadedDocument) {
                $user = User::create(collect($data)->except(['professionel', 'client'])->all());

                if ($data['role'] === 'professionel' && !empty($data['professionel'])) {
                    $professionelData = $this->extractProfileData($data['professionel']);

                    if ($professionelData !== []) {
                        if (!empty($professionelData['document'])) {
                            $uploadedDocument = $this->uploadFile($professionelData['document'], 'professionel-documents');
                            $professionelData['document'] = $uploadedDocument;
                        }

                        Professionel::create([
                            ...$professionelData,
                            'user_id' => $user->id,
                        ]);
                    }
                }

                if ($data['role'] === 'client' && !empty($data['client'])) {
                    $clientData = $this->extractProfileData($data['client']);

                    if ($clientData !== []) {
                        Client::create([
                            ...$clientData,
                            'user_id' => $user->id,
                        ]);
                    }
                }

                return $user;
            });

            $otp = $this->generateOtp();
            $this->storeOtp($user->telephone, $otp);

            $message = "Bonjour {$user->first_name}, votre code de verification Kegny est : {$otp}. Il expire dans 5 minutes.";
            SendMessageEvent::dispatch($user->telephone, $message);

            $this->dispatchVerificationEmail($user);

            $token = $user->createToken('auth-token')->plainTextToken;

            return $this->authenticatedUserResponse(
                $user,
                $token,
                'Inscription réussie. Veuillez vérifier votre téléphone et votre adresse e-mail.'
            );
        } catch (\Throwable $e) {
            if ($uploadedDocument) {
                $this->deleteFile($uploadedDocument, 'professionel-documents');
            }

            return $this->errorResponse(
                'Une erreur s\'est produite lors de l\'inscription.',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    public function verifyOtp(Request $request)
    {
        try {
            // Validate the OTP and telephone number
            $validator = Validator::make($request->all(), [
                'telephone' => ['required', 'string', 'exists:users,telephone'],
                'otp' => ['required', 'string', 'size:6'],
            ], [
                'telephone.required' => 'Le numéro de téléphone est obligatoire.',
                'telephone.exists' => 'Aucun compte trouvé avec ce numéro de téléphone.',
                'otp.required' => 'Le code de vérification est obligatoire.',
                'otp.size' => 'Le code de vérification doit comporter 6 chiffres.',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse(
                    'Erreur de validation.',
                    $validator->errors(),
                    422
                );
            }

            $telephone = $request->input('telephone');
            $otp = $request->input('otp');

            // Check if the OTP is valid for the given telephone number
            $cachedOtp = Cache::get("otp:{$telephone}");

            // Decrypt OTP if encryption is enabled
            if ($cachedOtp && config('constants.security.encrypt_otp', true)) {
                try {
                    $cachedOtp = decrypt($cachedOtp);
                } catch (\Exception $e) {
                    $cachedOtp = null;
                }
            }

            if (!$cachedOtp || $cachedOtp !== $otp) {
                return $this->errorResponse(
                    'Code de vérification invalide ou expiré.',
                    ['otp' => 'Le code de vérification est incorrect ou a expiré.'],
                    401
                );
            }

            // Find user
            $user = User::where('telephone', $telephone)->first();

            if (!$user->is_active) {
                return $this->errorResponse(
                    'Compte inactif.',
                    ['status' => 'Votre compte a été désactivé. Veuillez contacter l\'administrateur.'],
                    403
                );
            }

            // Delete OTP from cache
            Cache::forget("otp:{$telephone}");

            // Mark phone as verified
            if (!$user->is_phone_verified) {
                $user->is_phone_verified = true;

                // professionel is approved manually after super_admin/admin verifies documents and profile
                if ($user->role == 'client') {
                    $user->is_approved = true;
                    $user->phone_verified_at = now();
                }

                $user->save();
            }

            // Generate authentication token
            $token = $user->createToken('auth-token')->plainTextToken;

            // Return user resource with token, load profesionnel and client relationships
            return $this->authenticatedUserResponse(
                $user,
                $token,
                'Numéro de téléphone vérifié avec succès.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Une erreur s\'est produite lors de la vérification.',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Resend OTP verification code to the user.
     */
    public function resendOtp(Request $request)
    {
        try {
            // Validate the telephone number
            $validator = Validator::make($request->all(), [
                'telephone' => ['required', 'string', 'exists:users,telephone'],
            ], [
                'telephone.required' => 'Le numéro de téléphone est obligatoire.',
                'telephone.exists' => 'Aucun compte trouvé avec ce numéro de téléphone.',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse(
                    'Erreur de validation.',
                    $validator->errors(),
                    422
                );
            }

            $telephone = $request->input('telephone');

            // Use rate limiting to prevent abuse - max 3 times per hour per telephone number
            $key = 'resend-otp:' . $telephone;

            if (RateLimiter::tooManyAttempts($key, 3)) {
                $seconds = RateLimiter::availableIn($key);
                $minutes = ceil($seconds / 60);

                return $this->errorResponse(
                    'Trop de tentatives.',
                    ['rate_limit' => "Vous avez dépassé le nombre maximum de tentatives. Veuillez réessayer dans {$minutes} minute(s)."],
                    429
                );
            }

            // Find user
            $user = User::where('telephone', $telephone)->first();

            if (!$user->is_active) {
                return $this->errorResponse(
                    'Compte inactif.',
                    ['status' => 'Votre compte a été désactivé. Veuillez contacter l\'administrateur.'],
                    403
                );
            }

            // Generate OTP using config
            $otpLength = config('constants.otp.length', 6);
            $maxOtp = (int) str_repeat('5', $otpLength);
            $minOtp = (int) ('1' . str_repeat('0', $otpLength - 1));
            $otp = str_pad((string) random_int($minOtp, $maxOtp), $otpLength, '0', STR_PAD_LEFT);

            // Store encrypted OTP in cache
            $expiryMinutes = config('constants.otp.expiry_minutes', 5);
            $encryptedOtp = config('constants.security.encrypt_otp', true) ? encrypt($otp) : $otp;
            Cache::put("otp:{$telephone}", $encryptedOtp, now()->addMinutes($expiryMinutes));

            // Send the new OTP to the user's telephone number
            $message = "Votre nouveau code de vérification est: {$otp}. Ce code est valide pendant 5 minutes.";
            SendMessageEvent::dispatch($telephone, $message);

            // Increment rate limiter
            RateLimiter::hit($key, 3600); // 1 hour

            return $this->noContentSuccessResponse(
                'Un nouveau code de vérification a été envoyé à votre numéro de téléphone.',
                200
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Une erreur s\'est produite lors de l\'envoi du code.',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /** This method is used to login a user regardless of the role */
    public function login(LoginRequest $request)
    {
        try {
            // Validate request
            $data = $request->validated();

            $login = $data['login'];
            $password = $data['password'];

            // Determine if login is email or telephone
            $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'telephone';

            // Find user
            $user = User::where($field, $login)->first();

            if (!$user) {
                return $this->errorResponse(
                    'Identifiants invalides.',
                    ['login' => 'Aucun compte trouvé avec ces identifiants.'],
                    401
                );
            }

            if (!$user->is_active) {
                return $this->errorResponse(
                    'Compte inactif.',
                    ['status' => 'Votre compte a été désactivé. Veuillez contacter l\'administrateur.'],
                    403
                );
            }

            // If password is provided, authenticate with password
            if ($password) {
                if (!Hash::check($password, $user->password)) {
                    return $this->errorResponse(
                        'Identifiants invalides.',
                        ['password' => 'Mot de passe incorrect.'],
                        401
                    );
                }

                // Generate authentication token
                $token = $user->createToken('auth-token')->plainTextToken;

                // Return user resource with token, load profesionnel and client relationships
                return $this->authenticatedUserResponse(
                    $user,
                    $token,
                    'Connexion réussie.'
                );
            }

            // If no password and login is telephone, generate OTP
            if ($field === 'telephone') {
                // Generate OTP using config
                $otpLength = config('constants.otp.length', 6);
                $maxOtp = (int) str_repeat('9', $otpLength);
                $minOtp = (int) ('1' . str_repeat('0', $otpLength - 1));
                $otp = str_pad((string) random_int($minOtp, $maxOtp), $otpLength, '0', STR_PAD_LEFT);

                // Store encrypted OTP in cache
                $expiryMinutes = config('constants.otp.expiry_minutes', 5);
                $encryptedOtp = config('constants.security.encrypt_otp', true) ? encrypt($otp) : $otp;
                Cache::put("otp:{$user->telephone}", $encryptedOtp, now()->addMinutes($expiryMinutes));

                // Send OTP via SMS
                $message = "Votre code de vérification est: {$otp}. Ce code est valide pendant 5 minutes.";
                SendMessageEvent::dispatch($user->telephone, $message);

                return $this->noContentSuccessResponse(
                    'Un code de vérification a été envoyé à votre numéro de téléphone.',
                    200
                );
            }

            // If email without password, require password
            return $this->errorResponse(
                'Mot de passe requis.',
                ['password' => 'Le mot de passe est obligatoire pour la connexion par email.'],
                422
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Une erreur s\'est produite lors de la connexion.',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /** This method is used to resend the verification email to a user. */
    public function resendEmail(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user->is_active) {
                return $this->errorResponse(
                    'Compte inactif.',
                    ['status' => 'Votre compte a été désactivé. Veuillez contacter l\'administrateur.'],
                    403
                );
            }

            if ($user->is_email_verified) {
                return $this->noContentSuccessResponse(
                    'Votre adresse e-mail est déjà vérifiée.',
                    200
                );
            }

            $this->dispatchVerificationEmail($user);

            return $this->noContentSuccessResponse(
                'Un nouvel e-mail de vérification vous a été envoyé.',
                200
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                'Une erreur s\'est produite lors de l\'envoi de l\'e-mail de vérification.',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /** This method is used to verify a user's email address. */
    public function verifyEmail(string $uuid)
    {
        try {
            $user = User::where('uuid', $uuid)->first();

            if (!$user) {
                return $this->errorResponse(
                    'Lien de vérification invalide.',
                    ['uuid' => 'Aucun utilisateur trouvé pour ce lien de vérification.'],
                    404
                );
            }

            if (!$user->is_active) {
                return $this->errorResponse(
                    'Compte inactif.',
                    ['status' => 'Votre compte a été désactivé. Veuillez contacter l\'administrateur.'],
                    403
                );
            }

            if (!$user->is_email_verified) {
                $user->forceFill([
                    'is_email_verified' => true,
                    'email_verified_at' => now(),
                ])->save();
            }

            $token = $user->createToken('auth-token')->plainTextToken;

            return $this->authenticatedUserResponse(
                $user,
                $token,
                'Adresse e-mail vérifiée avec succès.'
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                'Une erreur s\'est produite lors de la vérification de l\'adresse e-mail.',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /** This method is used to logout a user */
    public function logout(Request $request)
    {
        try {
            // Delete current access token
            $request->user()->tokens()->delete();

            return $this->noContentSuccessResponse(
                'Déconnexion réussie.',
                200
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Une erreur s\'est produite lors de la déconnexion.',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    private function generateOtp(): string
    {
        $otpLength = (int) config('constants.otp.length', 6);
        $maxOtp = (int) str_repeat('9', $otpLength);
        $minOtp = (int) ('1' . str_repeat('0', $otpLength - 1));

        return str_pad((string) random_int($minOtp, $maxOtp), $otpLength, '0', STR_PAD_LEFT);
    }

    private function storeOtp(string $telephone, string $otp): void
    {
        $expiryMinutes = (int) config('constants.otp.expiry_minutes', 5);
        $encryptedOtp = config('constants.security.encrypt_otp', true) ? encrypt($otp) : $otp;

        Cache::put("otp:{$telephone}", $encryptedOtp, now()->addMinutes($expiryMinutes));
    }

    private function dispatchVerificationEmail(User $user): void
    {
        SendVerificationEmailEvent::dispatch(
            $user,
            route('auth.verify-email', ['uuid' => $user->uuid])
        );
    }

    private function authenticatedUserResponse(User $user, string $token, string $message, int $code = 200)
    {
        $user->loadMissing(['professionel', 'client']);

        return $this->successResponseWithToken(
            new UserResource($user),
            $token,
            $message,
            $code
        );
    }

    private function normalizeRole(string $role): string
    {
        return $role === 'profesionnel' ? 'professionel' : $role;
    }

    private function extractProfileData(array $profileData): array
    {
        $firstProfile = array_is_list($profileData)
            ? ($profileData[0] ?? [])
            : $profileData;

        return is_array($firstProfile) ? $firstProfile : [];
    }
}
