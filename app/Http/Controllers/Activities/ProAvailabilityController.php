<?php

namespace App\Http\Controllers\Activities;

use App\Http\Controllers\Controller;
use App\Http\Requests\activities\StoreProAvailabilityRequest;
use App\Http\Resources\ProAvailabilityResource;
use App\Models\Activities\ProAvailability;
use App\Services\PermissionService;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class ProAvailabilityController extends Controller
{
    use ApiResponses;

    public function __construct(
        private PermissionService $permissionService,
    ) {}

    /**
     * Only display authenticated user's availabilities
     * Display all availabilities for super_admin and admin
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = ProAvailability::query()->with('professionel');

        if (!in_array($user->role, ['super_admin', 'admin'], true)) {
            $query->where('professionel_id', $user->id);
        }

        $availabilities = $query
            ->orderBy('professionel_id')
            ->orderByRaw($this->dayOfWeekOrderSql())
            ->paginate($this->permissionService->perPage($request));

        return $this->successResponse([
            'availabilities' => ProAvailabilityResource::collection($availabilities->getCollection()),
            'pagination' => [
                'current_page' => $availabilities->currentPage(),
                'last_page' => $availabilities->lastPage(),
                'per_page' => $availabilities->perPage(),
                'total' => $availabilities->total(),
            ],
        ], 'Liste des disponibilités récupérée avec succès.');
    }

    /**
     * Only display authenticated user's availability
     */
    public function show(Request $request, ProAvailability $proAvailability)
    {
        $user = $request->user();

        if (
            !in_array($user->role, ['super_admin', 'admin'], true)
            && $proAvailability->professionel_id !== $user->id
        ) {
            return $this->errorResponse(
                'Disponibilité introuvable.',
                ['availability' => 'Vous ne pouvez accéder qu\'à vos propres disponibilités.'],
                404
            );
        }

        $proAvailability->loadMissing('professionel');

        return $this->successResponse(
            new ProAvailabilityResource($proAvailability),
            'Disponibilité récupérée avec succès.'
        );
    }

    /**
     * Only user of role professionel can perform this action to create his own availabilities
     * Cannot create two availabilities for the same day of week
     * Cannot create more than seven days of availabilities
     */
    public function store(StoreProAvailabilityRequest $request)
    {
        if ($authorization = $this->permissionService->authorizeRole($request->user(), 'professionel')) {
            return $authorization;
        }

        $user = $request->user();
        $data = $request->validated();

        if ($user->availabilities()->count() >= 7) {
            return $this->errorResponse(
                'Limite de disponibilités atteinte.',
                ['availability' => 'Vous ne pouvez pas créer plus de sept jours de disponibilité.'],
                422
            );
        }

        $dayExists = $user->availabilities()
            ->where('day_of_week', $data['day_of_week'])
            ->exists();

        if ($dayExists) {
            return $this->errorResponse(
                'Disponibilité déjà existante.',
                ['day_of_week' => 'Vous avez déjà une disponibilité pour ce jour de la semaine.'],
                422
            );
        }

        $availability = $user->availabilities()->create($data);
        $availability->loadMissing('professionel');

        return $this->successResponse(
            new ProAvailabilityResource($availability),
            'Disponibilité créée avec succès.',
            201
        );
    }

    /**
     * Only user of role professionel can perform this action to update his own availabilities
     */
    public function update(StoreProAvailabilityRequest $request, ProAvailability $proAvailability)
    {
        if ($authorization = $this->permissionService->authorizeRole($request->user(), 'professionel')) {
            return $authorization;
        }

        $user = $request->user();

        if ($proAvailability->professionel_id !== $user->id) {
            return $this->errorResponse(
                'Action non autorisée.',
                ['availability' => 'Vous ne pouvez modifier que vos propres disponibilités.'],
                403
            );
        }

        $data = $request->validated();
        $dayExists = $user->availabilities()
            ->where('day_of_week', $data['day_of_week'])
            ->where('id', '!=', $proAvailability->id)
            ->exists();

        if ($dayExists) {
            return $this->errorResponse(
                'Disponibilité déjà existante.',
                ['day_of_week' => 'Vous avez déjà une disponibilité pour ce jour de la semaine.'],
                422
            );
        }

        $proAvailability->update($data);
        $proAvailability->loadMissing('professionel');

        return $this->successResponse(
            new ProAvailabilityResource($proAvailability),
            'Disponibilité mise à jour avec succès.'
        );
    }

    /**
     * Only user of role professionel can perform this action to delete his own availabilities
     */
    public function destroy(Request $request, ProAvailability $proAvailability)
    {
        if ($authorization = $this->permissionService->authorizeRole($request->user(), 'professionel')) {
            return $authorization;
        }

        if ($proAvailability->professionel_id !== $request->user()->id) {
            return $this->errorResponse(
                'Action non autorisée.',
                ['availability' => 'Vous ne pouvez supprimer que vos propres disponibilités.'],
                403
            );
        }

        $proAvailability->delete();

        return $this->noContentSuccessResponse(
            'Disponibilité supprimée avec succès.',
            200
        );
    }

    private function dayOfWeekOrderSql(): string
    {
        return "CASE day_of_week
            WHEN 'lundi' THEN 1
            WHEN 'mardi' THEN 2
            WHEN 'mercredi' THEN 3
            WHEN 'jeudi' THEN 4
            WHEN 'vendredi' THEN 5
            WHEN 'samedi' THEN 6
            WHEN 'dimanche' THEN 7
            ELSE 8
        END";
    }
}
