<?php

namespace App\Http\Controllers\Activities;

use App\Http\Controllers\Controller;
use App\Http\Requests\activities\StoreAgeRangeRequest;
use App\Http\Resources\AgeRangeResource;
use App\Models\Activities\AgeRange;
use App\Services\PermissionService;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class AgeRangeController extends Controller
{
    use ApiResponses;

    public function __construct(
        private PermissionService $permissionService,
    ) {}

    /**
     * Get a listing of the resource. this method doesn't require auth:sanctum middleware
     *
     */
    public function index(Request $request)
    {
        $query = AgeRange::query();

        $ageRanges = $query->orderBy('name')->paginate($this->permissionService->perPage($request));

        return $this->successResponse([
            'age_ranges' => AgeRangeResource::collection($ageRanges->getCollection()),
            'pagination' => [
                'current_page' => $ageRanges->currentPage(),
                'last_page' => $ageRanges->lastPage(),
                'per_page' => $ageRanges->perPage(),
                'total' => $ageRanges->total(),
            ],
        ], 'Liste des tranches d\'âge récupérée avec succès.');
    }

    public function show(Request $request, AgeRange $ageRange)
    {
        return $this->successResponse(
            new AgeRangeResource($ageRange),
            'Tranche d\'âge récupérée avec succès.'
        );
    }

    public function store(StoreAgeRangeRequest $request)
    {
        if ($authorization = $this->permissionService->authorizeRoles($request->user(), ['super_admin', 'admin'])) {
            return $authorization;
        }

        $ageRange = AgeRange::create($request->validated());

        return $this->successResponse(
            new AgeRangeResource($ageRange),
            'Tranche d\'âge créée avec succès.',
            201
        );
    }

    public function update(StoreAgeRangeRequest $request, AgeRange $ageRange)
    {
        if ($authorization = $this->permissionService->authorizeRoles($request->user(), ['super_admin', 'admin'])) {
            return $authorization;
        }

        $ageRange->update($request->validated());

        return $this->successResponse(
            new AgeRangeResource($ageRange),
            'Tranche d\'âge mise à jour avec succès.'
        );
    }

    public function switchStatus(Request $request, AgeRange $ageRange)
    {
        if ($authorization = $this->permissionService->authorizeRoles($request->user(), ['super_admin', 'admin'])) {
            return $authorization;
        }

        $ageRange->is_active = !$ageRange->is_active;
        $ageRange->save();

        return $this->successResponse(
            new AgeRangeResource($ageRange),
            $ageRange->is_active
                ? 'Tranche d\'âge activée avec succès.'
                : 'Tranche d\'âge désactivée avec succès.'
        );
    }

    public function destroy(Request $request, AgeRange $ageRange)
    {
        if ($authorization = $this->permissionService->authorizeRoles($request->user(), ['super_admin', 'admin'])) {
            return $authorization;
        }

        $ageRange->delete();

        return $this->noContentSuccessResponse(
            'Tranche d\'âge supprimée avec succès.',
            200
        );
    }
}
