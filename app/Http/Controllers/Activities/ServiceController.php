<?php

namespace App\Http\Controllers\Activities;

use App\Http\Controllers\Controller;
use App\Http\Requests\activities\StoreServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Activities\Service as ActivityService;
use App\Models\Salon;
use App\Services\PermissionService;
use App\Services\ServicePriceApprovalNotificationService;
use App\Traits\ApiResponses;
use App\Traits\CloudflareUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ServiceController extends Controller
{
    use ApiResponses;
    use CloudflareUpload;

    public function __construct(
        private PermissionService $permissionService,
        private ServicePriceApprovalNotificationService $priceApprovalNotificationService,
    ) {}

    public function index(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        $isAdmin = $this->permissionService->isAdmin($user);
        $isOwnerView = $user && $user->role === 'professionel' && !$isAdmin;

        $query = ActivityService::query()
            ->with([
                'professionel',
                'salon',
                'category',
                'currency',
                'servicePrices' => fn($priceQuery) => $this->scopeServicePricesForViewer($priceQuery, $isAdmin || $isOwnerView),
                'servicePrices.service.currency',
                'servicePrices.ageRange',
                'portfolios' => fn($portfolioQuery) => $this->scopePortfoliosForViewer($portfolioQuery, $isAdmin || $isOwnerView),
            ]);

        if ($isAdmin) {
            // Admins can view every service.
        } elseif ($isOwnerView) {
            $query->where('professionel_id', $user->id);
        } else {
            $query
                ->where('is_active', true)
                ->whereHas('professionel', fn($professionelQuery) => $professionelQuery
                    ->where('is_active', true)
                    ->where('is_approved', true));
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        if ($request->filled('salon_id')) {
            $query->where('salon_id', $request->integer('salon_id'));
        }

        if ($request->filled('search')) {
            $search = trim($request->string('search')->toString());
            $query->where('name', 'like', "%{$search}%");
        }

        $services = $query
            ->latest()
            ->paginate($this->permissionService->perPage($request));

        return $this->successResponse([
            'services' => ServiceResource::collection($services->getCollection()),
            'pagination' => [
                'current_page' => $services->currentPage(),
                'last_page' => $services->lastPage(),
                'per_page' => $services->perPage(),
                'total' => $services->total(),
            ],
        ], 'Liste des services récupérée avec succès.');
    }

    public function show(Request $request, ActivityService $service)
    {
        $user = Auth::guard('sanctum')->user();

        $service->loadMissing([
            'professionel',
            'salon',
            'category',
            'currency',
            'servicePrices' => fn($priceQuery) => $this->scopeServicePricesForViewer($priceQuery, $this->permissionService->canManageService($user, $service)),
            'servicePrices.service.currency',
            'servicePrices.ageRange',
            'portfolios' => fn($portfolioQuery) => $this->scopePortfoliosForViewer($portfolioQuery, $this->permissionService->canManageService($user, $service)),
        ]);

        if (!$this->permissionService->canViewService($user, $service)) {
            return $this->errorResponse(
                'Service introuvable.',
                ['service' => 'Ce service n\'est pas disponible.'],
                404
            );
        }

        return $this->successResponse(
            new ServiceResource($service),
            'Service récupéré avec succès.'
        );
    }

    public function store(StoreServiceRequest $request)
    {
        if ($authorization = $this->permissionService->authorizeRole($request->user(), 'professionel')) {
            return $authorization;
        }

        $user = $request->user();
        $data = $request->validated();
        $uploadedImages = [];
        $createdPriceCount = 0;

        if (!Salon::query()->where('id', $data['salon_id'])->where('owner_id', $user->id)->exists()) {
            return $this->errorResponse(
                'Action non autorisée.',
                ['salon_id' => 'Vous ne pouvez rattacher un service qu\'à l\'un de vos salons.'],
                403
            );
        }

        try {
            $service = DB::transaction(function () use ($user, $data, &$uploadedImages, &$createdPriceCount) {
                $prices = $data['prices'] ?? null;
                $images = $data['images'] ?? null;
                unset($data['prices'], $data['images']);

                $service = $user->services()->create($data);

                if (is_array($prices)) {
                    foreach ($prices as $price) {
                        $service->servicePrices()->create([
                            'age_range_id' => $price['age_range_id'],
                            'price' => $price['price'],
                            'is_approved' => false,
                        ]);
                    }

                    $createdPriceCount = count($prices);
                }

                if (is_array($images)) {
                    foreach ($images as $image) {
                        $uploadedImage = $this->uploadImage($image, 'pro-portfolios');
                        $uploadedImages[] = $uploadedImage;

                        $service->portfolios()->create([
                            'professionel_id' => $user->id,
                            'image' => $uploadedImage,
                            'is_active' => true,
                        ]);
                    }
                }

                return $service;
            });
        } catch (\Throwable $e) {
            foreach ($uploadedImages as $uploadedImage) {
                $this->deleteImage($uploadedImage, 'pro-portfolios');
            }

            return $this->errorResponse(
                'Une erreur s\'est produite lors de la création du service.',
                ['error' => $e->getMessage()],
                500
            );
        }

        $service->load([
            'professionel',
            'salon',
            'category',
            'currency',
            'servicePrices.service.currency',
            'servicePrices.ageRange',
            'portfolios',
        ]);

        if ($createdPriceCount > 0) {
            $this->priceApprovalNotificationService->sendPendingApprovalNotifications($service, $createdPriceCount);
        }

        return $this->successResponse(
            new ServiceResource($service),
            'Service créé avec succès.',
            201
        );
    }

    public function update(StoreServiceRequest $request, ActivityService $service)
    {
        if ($authorization = $this->permissionService->authorizeRole($request->user(), 'professionel')) {
            return $authorization;
        }

        $user = $request->user();
        $data = $request->validated();
        $uploadedImages = [];
        $updatedPriceCount = 0;

        if ($service->professionel_id !== $user->id) {
            return $this->errorResponse(
                'Action non autorisée.',
                ['service' => 'Vous ne pouvez modifier que vos propres services.'],
                403
            );
        }

        if (!Salon::query()->where('id', $data['salon_id'])->where('owner_id', $user->id)->exists()) {
            return $this->errorResponse(
                'Action non autorisée.',
                ['salon_id' => 'Vous ne pouvez rattacher un service qu\'à l\'un de vos salons.'],
                403
            );
        }

        try {
            DB::transaction(function () use ($service, $user, $data, &$uploadedImages, &$updatedPriceCount) {
                $prices = array_key_exists('prices', $data) ? $data['prices'] : null;
                $images = $data['images'] ?? null;
                unset($data['prices'], $data['images']);

                $service->update($data);

                if ($prices !== null) {
                    $service->servicePrices()->delete();

                    foreach ($prices as $price) {
                        $service->servicePrices()->create([
                            'age_range_id' => $price['age_range_id'],
                            'price' => $price['price'],
                            'is_approved' => false,
                        ]);
                    }

                    $updatedPriceCount = count($prices);
                }

                if (is_array($images)) {
                    foreach ($images as $image) {
                        $uploadedImage = $this->uploadImage($image, 'pro-portfolios');
                        $uploadedImages[] = $uploadedImage;

                        $service->portfolios()->create([
                            'professionel_id' => $user->id,
                            'image' => $uploadedImage,
                            'is_active' => true,
                        ]);
                    }
                }
            });
        } catch (\Throwable $e) {
            foreach ($uploadedImages as $uploadedImage) {
                $this->deleteImage($uploadedImage, 'pro-portfolios');
            }

            return $this->errorResponse(
                'Une erreur s\'est produite lors de la mise à jour du service.',
                ['error' => $e->getMessage()],
                500
            );
        }

        $service->refresh()->load([
            'professionel',
            'salon',
            'category',
            'currency',
            'servicePrices.service.currency',
            'servicePrices.ageRange',
            'portfolios',
        ]);

        if ($updatedPriceCount > 0) {
            $this->priceApprovalNotificationService->sendPendingApprovalNotifications($service, $updatedPriceCount);
        }

        return $this->successResponse(
            new ServiceResource($service),
            'Service mis à jour avec succès.'
        );
    }

    public function destroy(Request $request, ActivityService $service)
    {
        if (!$this->permissionService->canManageService($request->user(), $service)) {
            return $this->errorResponse(
                'Action non autorisée.',
                ['service' => 'Vous ne pouvez supprimer que vos propres services.'],
                403
            );
        }

        $portfolioImages = $service->portfolios()->pluck('image')->filter()->all();

        foreach ($portfolioImages as $image) {
            $this->deleteImage($image, 'pro-portfolios');
        }

        $service->delete();

        return $this->noContentSuccessResponse(
            'Service supprimé avec succès.',
            200
        );
    }

    private function scopeServicePricesForViewer($query, bool $canViewAll)
    {
        if (!$canViewAll) {
            $query->where('is_approved', true);
        }

        return $query->orderBy('age_range_id');
    }

    private function scopePortfoliosForViewer($query, bool $canViewAll)
    {
        if (!$canViewAll) {
            $query->where('is_active', true);
        }

        return $query->latest();
    }
}
