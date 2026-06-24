<?php

namespace App\Http\Controllers\auth;

use App\Events\SendMessageEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\user\StoreUserRequest;
use App\Http\Requests\user\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\PermissionService;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    use ApiResponses;

    public function __construct(
        private PermissionService $permissionService,
    ) {}

    /** Display all users in database of role professionel & client */
    public function index(Request $request)
    {
        $query = User::query()
            ->with(['professionel', 'client', 'defaultCurrency'])
            ->whereIn('role', ['professionel', 'client']);

        if ($request->filled('role') && in_array($request->string('role')->toString(), ['professionel', 'client'], true)) {
            $query->where('role', $request->string('role')->toString());
        }

        if ($request->filled('status')) {
            $status = filter_var($request->input('status'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            if ($status !== null) {
                $query->where('is_active', $status);
            }
        }

        if ($request->filled('search')) {
            $search = trim($request->string('search')->toString());

            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('telephone', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate($this->permissionService->perPage($request));

        return $this->paginatedUsersResponse($users, 'Liste des professionnels et clients récupérée avec succès.');
    }

    /** getAdmins to display all super_admin, admin and user in database */
    public function getAdmins(Request $request)
    {
        $query = User::query()
            ->with(['professionel', 'client', 'defaultCurrency'])
            ->whereIn('role', ['super_admin', 'admin', 'user']);

        if ($request->filled('role') && in_array($request->string('role')->toString(), ['super_admin', 'admin', 'user'], true)) {
            $query->where('role', $request->string('role')->toString());
        }

        if ($request->filled('search')) {
            $search = trim($request->string('search')->toString());

            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('telephone', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate($this->permissionService->perPage($request));

        return $this->paginatedUsersResponse($users, 'Liste des administrateurs et utilisateurs récupérée avec succès.');
    }

    /** updateAvatar to update avatar of authenticated user in database */
    public function updateAvatar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'avatar.required' => 'La photo de profil est obligatoire.',
            'avatar.image' => 'Le fichier doit être une image valide.',
            'avatar.mimes' => 'La photo de profil doit être au format jpg, jpeg, png ou webp.',
            'avatar.max' => 'La photo de profil ne doit pas dépasser 5 Mo.',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Erreur de validation.',
                $validator->errors(),
                422
            );
        }

        $user = $request->user();

        if ($user->avatar) {
            $user->deleteImage($user->avatar, 'profile-photos');
        }

        $user->avatar = $user->uploadImage($request->file('avatar'), 'profile-photos');
        $user->save();
        $user->loadMissing(['professionel', 'client', 'defaultCurrency']);

        return $this->successResponse(
            new UserResource($user),
            'Photo de profil mise à jour avec succès.'
        );
    }

    /** approveUser to approve or disapprove a user in database, only super_admin and admin can perform this action */
    public function approveUser(Request $request, string $uuid)
    {
        if ($authorization = $this->permissionService->authorizeRoles($request->user(), ['super_admin', 'admin'])) {
            return $authorization;
        }

        $user = User::with(['professionel', 'client', 'defaultCurrency'])->where('uuid', $uuid)->first();

        if (!$user) {
            return $this->errorResponse(
                'Utilisateur introuvable.',
                ['uuid' => 'Aucun utilisateur trouvé pour cet identifiant.'],
                404
            );
        }

        if ($request->user()->role !== 'super_admin' && $user->role === 'super_admin') {
            return $this->errorResponse(
                'Action non autorisée.',
                ['role' => 'Seul un super administrateur peut modifier un super administrateur.'],
                403
            );
        }

        $user->is_approved = !$user->is_approved;
        $user->save();

        if ($user->role === 'professionel' && !empty($user->telephone)) {
            SendMessageEvent::dispatch($user->telephone, $this->approvalStatusSms($user->is_approved));
        }

        return $this->successResponse(
            new UserResource($user),
            $user->is_approved ? 'Utilisateur approuvé avec succès.' : 'Utilisateur désapprouvé avec succès.'
        );
    }

    /** activeUser to activate or deactivate a user in database, only super_admin and admin can perform this action */
    public function activeUser(Request $request, string $uuid)
    {
        if ($authorization = $this->permissionService->authorizeRoles($request->user(), ['super_admin', 'admin'])) {
            return $authorization;
        }

        $user = User::with(['professionel', 'client', 'defaultCurrency'])->where('uuid', $uuid)->first();

        if (!$user) {
            return $this->errorResponse(
                'Utilisateur introuvable.',
                ['uuid' => 'Aucun utilisateur trouvé pour cet identifiant.'],
                404
            );
        }

        if ($request->user()->role !== 'super_admin' && $user->role === 'super_admin') {
            return $this->errorResponse(
                'Action non autorisée.',
                ['role' => 'Seul un super administrateur peut modifier un super administrateur.'],
                403
            );
        }

        $user->is_active = !$user->is_active;
        $user->save();

        return $this->successResponse(
            new UserResource($user),
            $user->is_active ? 'Utilisateur activé avec succès.' : 'Utilisateur désactivé avec succès.'
        );
    }

    /** store to store new user in database only authenticated user of role super_admin or admin can store a new user */
    public function store(StoreUserRequest $request)
    {
        if ($authorization = $this->permissionService->authorizeRoles($request->user(), ['super_admin', 'admin'])) {
            return $authorization;
        }

        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);
        $user->loadMissing(['professionel', 'client']);

        return $this->successResponse(
            new UserResource($user),
            'Utilisateur créé avec succès.',
            201
        );
    }

    public function show(string $uuid)
    {
        $user = User::with(['professionel', 'client', 'defaultCurrency'])->where('uuid', $uuid)->first();

        if (!$user) {
            return $this->errorResponse(
                'Utilisateur introuvable.',
                ['uuid' => 'Aucun utilisateur trouvé pour cet identifiant.'],
                404
            );
        }

        return $this->successResponse(
            new UserResource($user),
            'Utilisateur récupéré avec succès.'
        );
    }

    /** me to display authenticated user information */
    public function me(Request $request)
    {
        $user = $request->user()->loadMissing(['professionel', 'client', 'defaultCurrency']);

        return $this->successResponse(
            new UserResource($user),
            'Informations de l\'utilisateur récupérées avec succès.'
        );
    }

    /** for authenticated user to update his own information */
    public function update(UpdateUserRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        unset($data['current_password']);

        if (array_key_exists('email', $data) && $data['email'] !== $user->email) {
            $data['is_email_verified'] = false;
            $data['email_verified_at'] = null;
        }

        if (array_key_exists('telephone', $data) && $data['telephone'] !== $user->telephone) {
            $data['is_phone_verified'] = false;
            $data['phone_verified_at'] = null;
        }

        $user->update($data);
        $user->loadMissing(['professionel', 'client', 'defaultCurrency']);

        return $this->successResponse(
            new UserResource($user),
            'Profil mis à jour avec succès.'
        );
    }

    /** Only super_admin can perfom this action */
    public function destroy(Request $request, string $uuid)
    {
        if ($authorization = $this->permissionService->authorizeRoles($request->user(), ['super_admin'])) {
            return $authorization;
        }

        $user = User::where('uuid', $uuid)->first();

        if (!$user) {
            return $this->errorResponse(
                'Utilisateur introuvable.',
                ['uuid' => 'Aucun utilisateur trouvé pour cet identifiant.'],
                404
            );
        }

        if ($request->user()->is($user)) {
            return $this->errorResponse(
                'Action non autorisée.',
                ['user' => 'Vous ne pouvez pas supprimer votre propre compte.'],
                422
            );
        }

        $user->delete();

        return $this->noContentSuccessResponse(
            'Utilisateur supprimé avec succès.',
            200
        );
    }

    private function paginatedUsersResponse(LengthAwarePaginator $users, string $message)
    {
        return $this->successResponse([
            'users' => UserResource::collection($users->getCollection()),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ], $message);
    }

    private function approvalStatusSms(bool $isApproved): string
    {
        return $isApproved
            ? 'Kegny: votre compte pro est approuvé. Vous pouvez maintenant recevoir des reservations.'
            : 'Kegny: votre compte pro a ete desapprouvé. Mettez votre profil a jour puis contactez le support.';
    }
}
