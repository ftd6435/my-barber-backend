<?php

namespace App\Http\Controllers\Activities;

use App\Http\Controllers\Controller;
use App\Http\Requests\activities\StoreServicePriceRequest;
use App\Http\Resources\ServicePriceResource;
use App\Models\Activities\Service as ActivityService;
use App\Models\Activities\ServicePrice;
use App\Services\PermissionService;
use App\Services\ServicePriceApprovalNotificationService;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ServicePriceController extends Controller
{
    use ApiResponses;

    public function __construct(
        private PermissionService $permissionService,
        private ServicePriceApprovalNotificationService $priceApprovalNotificationService,
    ) {
    }

    public function index(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        $query = ServicePrice::query()->with(['service.professionel', 'ageRange']);

        if ($this->permissionService->isAdmin($user)) {
            // Admins can view every price.
        } elseif ($user && $user->role === 'professionel') {
            $query->whereHas('service', fn ($serviceQuery) => $serviceQuery->where('professionel_id', $user->id));
        } else {
            $query
                ->where('is_approved', true)
                ->whereHas('service', fn ($serviceQuery) => $serviceQuery
                    ->where('is_active', true)
                    ->whereHas('professionel', fn ($professionelQuery) => $professionelQuery
                        ->where('is_active', true)
                        ->where('is_approved', true)));
        }

        if ($request->filled('service_id')) {
            $query->where('service_id', $request->integer('service_id'));
        }

        $prices = $query
            ->latest()
            ->paginate($this->permissionService->perPage($request));

        return $this->successResponse([
            'service_prices' => ServicePriceResource::collection($prices->getCollection()),
            'pagination' => [
                'current_page' => $prices->currentPage(),
                'last_page' => $prices->lastPage(),
                'per_page' => $prices->perPage(),
                'total' => $prices->total(),
            ],
        ], 'Liste des prix de service récupérée avec succès.');
    }

    public function show(Request $request, ServicePrice $servicePrice)
    {
        $user = Auth::guard('sanctum')->user();
        $servicePrice->loadMissing(['service.professionel', 'ageRange']);

        if (!$this->permissionService->canViewServicePrice($user, $servicePrice)) {
            return $this->errorResponse(
                'Prix de service introuvable.',
                ['service_price' => 'Ce prix de service n\'est pas disponible.'],
                404
            );
        }

        return $this->successResponse(
            new ServicePriceResource($servicePrice),
            'Prix de service récupéré avec succès.'
        );
    }

    public function store(StoreServicePriceRequest $request)
    {
        if ($authorization = $this->permissionService->authorizeRole($request->user(), 'professionel')) {
            return $authorization;
        }

        $service = ActivityService::with('professionel')->findOrFail($request->integer('service_id'));

        if (!$this->permissionService->canManageService($request->user(), $service)) {
            return $this->errorResponse(
                'Action non autorisée.',
                ['service_id' => 'Vous ne pouvez ajouter un prix qu\'à l\'un de vos services.'],
                403
            );
        }

        if ($service->servicePrices()->where('age_range_id', $request->integer('age_range_id'))->exists()) {
            return $this->errorResponse(
                'Prix déjà existant.',
                ['age_range_id' => 'Ce service possède déjà un prix pour cette tranche d\'âge.'],
                422
            );
        }

        $servicePrice = $service->servicePrices()->create([
            'age_range_id' => $request->integer('age_range_id'),
            'price' => $request->input('price'),
            'is_approved' => false,
        ]);

        $servicePrice->loadMissing(['service.professionel', 'ageRange']);
        $this->priceApprovalNotificationService->sendPendingApprovalNotifications($service, 1);

        return $this->successResponse(
            new ServicePriceResource($servicePrice),
            'Prix de service créé avec succès.',
            201
        );
    }

    public function update(StoreServicePriceRequest $request, ServicePrice $servicePrice)
    {
        if ($authorization = $this->permissionService->authorizeRole($request->user(), 'professionel')) {
            return $authorization;
        }

        $servicePrice->loadMissing('service.professionel');

        if (!$this->permissionService->canManageServicePrice($request->user(), $servicePrice)) {
            return $this->errorResponse(
                'Action non autorisée.',
                ['service_price' => 'Vous ne pouvez modifier que les prix de vos propres services.'],
                403
            );
        }

        $targetService = ActivityService::with('professionel')->findOrFail($request->integer('service_id'));

        if (!$this->permissionService->canManageService($request->user(), $targetService)) {
            return $this->errorResponse(
                'Action non autorisée.',
                ['service_id' => 'Vous ne pouvez rattacher ce prix qu\'à l\'un de vos services.'],
                403
            );
        }

        $duplicateExists = ServicePrice::query()
            ->where('service_id', $targetService->id)
            ->where('age_range_id', $request->integer('age_range_id'))
            ->where('id', '!=', $servicePrice->id)
            ->exists();

        if ($duplicateExists) {
            return $this->errorResponse(
                'Prix déjà existant.',
                ['age_range_id' => 'Ce service possède déjà un prix pour cette tranche d\'âge.'],
                422
            );
        }

        $servicePrice->update([
            'service_id' => $targetService->id,
            'age_range_id' => $request->integer('age_range_id'),
            'price' => $request->input('price'),
            'is_approved' => false,
        ]);

        $servicePrice->loadMissing(['service.professionel', 'ageRange']);
        $this->priceApprovalNotificationService->sendPendingApprovalNotifications($servicePrice->service, 1);

        return $this->successResponse(
            new ServicePriceResource($servicePrice),
            'Prix de service mis à jour avec succès.'
        );
    }

    public function destroy(Request $request, ServicePrice $servicePrice)
    {
        if ($authorization = $this->permissionService->authorizeRole($request->user(), 'professionel')) {
            return $authorization;
        }

        $servicePrice->loadMissing('service.professionel');

        if (!$this->permissionService->canManageServicePrice($request->user(), $servicePrice)) {
            return $this->errorResponse(
                'Action non autorisée.',
                ['service_price' => 'Vous ne pouvez supprimer que les prix de vos propres services.'],
                403
            );
        }

        $servicePrice->delete();

        return $this->noContentSuccessResponse(
            'Prix de service supprimé avec succès.',
            200
        );
    }
}
