<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSalonRequest;
use App\Http\Resources\SalonResource;
use App\Models\Salon;
use App\Services\PermissionService;
use App\Traits\ApiResponses;
use App\Traits\CloudflareUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalonController extends Controller
{
    use ApiResponses, CloudflareUpload;

    public function __construct(
        private PermissionService $permissionService,
    ) {
    }

    /**
     * Display a listing of the resource of all salons regardless of the owner and is public not protected with auth:sanctum middleware.
     */
    public function index(Request $request)
    {
        $perPage = $this->permissionService->perPage($request);
        $salons = Salon::query()
            ->with('owner')
            ->whereHas('owner', function ($query) {
                $query->where('is_active', true)
                    ->where('is_approved', true);
            })
            ->latest()
            ->paginate($perPage);

        return $this->successResponse([
            'salons' => SalonResource::collection($salons->getCollection()),
            'pagination' => [
                'current_page' => $salons->currentPage(),
                'last_page' => $salons->lastPage(),
                'per_page' => $salons->perPage(),
                'total' => $salons->total(),
            ],
        ], 'Liste des salons récupérée avec succès.');
    }

    /**
     * Display a list of salons of authenticated user.
     */
    public function getProfessionelSalons(Request $request)
    {
        $user = $request->user();
        $salons = $user->salons()->with('owner')->latest()->get();

        return $this->successResponse(
            SalonResource::collection($salons),
            'Liste des salons de l\'utilisateur récupérée avec succès.'
        );
    }

    public function show(Request $request, string $uuid)
    {
        $salon = Salon::query()
            ->with('owner')
            ->where('uuid', $uuid)
            ->first();

        if (!$salon) {
            return $this->errorResponse(
                'Salon introuvable.',
                ['uuid' => 'Aucun salon trouvé pour cet identifiant.'],
                404
            );
        }

        if (!$this->permissionService->canViewSalon($request->user(), $salon)) {
            return $this->errorResponse(
                'Salon introuvable.',
                ['salon' => 'Ce salon n\'est pas disponible.'],
                404
            );
        }

        return $this->successResponse(
            new SalonResource($salon),
            'Salon récupéré avec succès.'
        );
    }

    public function switchActive(Request $request, string $uuid)
    {
        $user = $request->user();
        $salon = Salon::with('owner')->where('uuid', $uuid)->first();

        if (!$salon) {
            return $this->errorResponse(
                'Salon introuvable.',
                ['uuid' => 'Aucun salon trouvé pour cet identifiant.'],
                404
            );
        }

        if (!$this->permissionService->canManageSalon($user, $salon)) {
            return $this->errorResponse(
                'Action non autorisée.',
                ['salon' => 'Vous ne pouvez pas modifier le statut de ce salon.'],
                403
            );
        }

        $salon->is_active = !$salon->is_active;
        $salon->save();

        return $this->successResponse(
            new SalonResource($salon),
            $salon->is_active ? 'Salon activé avec succès.' : 'Salon désactivé avec succès.'
        );
    }

    public function store(StoreSalonRequest $request)
    {
        $user = $request->user();

        if ($user->role === 'client') {
            return $this->errorResponse(
                'Action non autorisée.',
                ['role' => 'Un client ne peut pas créer de salon.'],
                403
            );
        }

        $data = $request->validated();
        $uploadedLogo = null;
        $uploadedBanner = null;

        try {
            $salon = DB::transaction(function () use ($user, $data, &$uploadedLogo, &$uploadedBanner) {
                if (!empty($data['logo'])) {
                    $uploadedLogo = $this->uploadImage($data['logo'], 'salons-photos');
                    $data['logo'] = $uploadedLogo;
                }

                if (!empty($data['banner'])) {
                    $uploadedBanner = $this->uploadImage($data['banner'], 'salons-photos');
                    $data['banner'] = $uploadedBanner;
                }

                return Salon::create([
                    ...$data,
                    'owner_id' => $user->id,
                ]);
            });

            $salon->load('owner');

            return $this->successResponse(
                new SalonResource($salon),
                'Salon créé avec succès.',
                201
            );
        } catch (\Throwable $e) {
            if ($uploadedLogo) {
                $this->deleteImage($uploadedLogo, 'salons-photos');
            }

            if ($uploadedBanner) {
                $this->deleteImage($uploadedBanner, 'salons-photos');
            }

            return $this->errorResponse(
                'Une erreur s\'est produite lors de la création du salon.',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    public function update(StoreSalonRequest $request, string $uuid)
    {
        $user = $request->user();
        $salon = Salon::with('owner')->where('uuid', $uuid)->first();

        if (!$salon) {
            return $this->errorResponse(
                'Salon introuvable.',
                ['uuid' => 'Aucun salon trouvé pour cet identifiant.'],
                404
            );
        }

        if (!$user->is($salon->owner)) {
            return $this->errorResponse(
                'Action non autorisée.',
                ['salon' => 'Seul le propriétaire du salon peut le modifier.'],
                403
            );
        }

        $data = $request->validated();
        $newLogo = null;
        $newBanner = null;
        $oldLogo = $salon->logo;
        $oldBanner = $salon->banner;

        try {
            DB::transaction(function () use ($salon, $data, &$newLogo, &$newBanner) {
                if (!empty($data['logo'])) {
                    $newLogo = $this->uploadImage($data['logo'], 'salons-photos');
                    $data['logo'] = $newLogo;
                } else {
                    unset($data['logo']);
                }

                if (!empty($data['banner'])) {
                    $newBanner = $this->uploadImage($data['banner'], 'salons-photos');
                    $data['banner'] = $newBanner;
                } else {
                    unset($data['banner']);
                }

                $salon->update($data);
            });

            if ($newLogo && $oldLogo) {
                $this->deleteImage($oldLogo, 'salons-photos');
            }

            if ($newBanner && $oldBanner) {
                $this->deleteImage($oldBanner, 'salons-photos');
            }

            $salon->refresh()->load('owner');

            return $this->successResponse(
                new SalonResource($salon),
                'Salon mis à jour avec succès.'
            );
        } catch (\Throwable $e) {
            if ($newLogo) {
                $this->deleteImage($newLogo, 'salons-photos');
            }

            if ($newBanner) {
                $this->deleteImage($newBanner, 'salons-photos');
            }

            return $this->errorResponse(
                'Une erreur s\'est produite lors de la mise à jour du salon.',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    public function destroy(Request $request, string $uuid)
    {
        $user = $request->user();
        $salon = Salon::with('owner')->where('uuid', $uuid)->first();

        if (!$salon) {
            return $this->errorResponse(
                'Salon introuvable.',
                ['uuid' => 'Aucun salon trouvé pour cet identifiant.'],
                404
            );
        }

        if (!$this->permissionService->canManageSalon($user, $salon)) {
            return $this->errorResponse(
                'Action non autorisée.',
                ['salon' => 'Vous ne pouvez pas supprimer ce salon.'],
                403
            );
        }

        if ($salon->logo) {
            $this->deleteImage($salon->logo, 'salons-photos');
        }

        if ($salon->banner) {
            $this->deleteImage($salon->banner, 'salons-photos');
        }

        $salon->delete();

        return $this->noContentSuccessResponse(
            'Salon supprimé avec succès.',
            200
        );
    }

}
