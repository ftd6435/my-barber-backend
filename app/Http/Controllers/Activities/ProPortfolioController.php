<?php

namespace App\Http\Controllers\Activities;

use App\Http\Controllers\Controller;
use App\Http\Requests\activities\StoreProPortfolioRequest;
use App\Http\Resources\ProPortfolioResource;
use App\Models\Activities\ProPortfolio;
use App\Models\Activities\Service as ActivityService;
use App\Services\PermissionService;
use App\Traits\ApiResponses;
use App\Traits\CloudflareUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProPortfolioController extends Controller
{
    use ApiResponses;
    use CloudflareUpload;

    public function __construct(
        private PermissionService $permissionService,
    ) {}

    public function index(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        $query = ProPortfolio::query()->with(['professionel', 'service.professionel']);

        if ($this->permissionService->isAdmin($user)) {
            // Admins can view every portfolio entry.
        } elseif ($user && $user->role === 'professionel') {
            $query->where('professionel_id', $user->id);
        } else {
            $query
                ->where('is_active', true)
                ->where(function ($portfolioQuery) {
                    $portfolioQuery
                        ->whereNull('service_id')
                        ->orWhereHas('service', fn($serviceQuery) => $serviceQuery
                            ->where('is_active', true)
                            ->whereHas('professionel', fn($professionelQuery) => $professionelQuery
                                ->where('is_active', true)
                                ->where('is_approved', true)));
                })
                ->whereHas('professionel', fn($professionelQuery) => $professionelQuery
                    ->where('is_active', true)
                    ->where('is_approved', true));
        }

        if ($request->filled('service_id')) {
            $query->where('service_id', $request->integer('service_id'));
        }

        if ($request->filled('professionel_id')) {
            $query->where('professionel_id', $request->integer('professionel_id'));
        }

        $portfolios = $query
            ->latest()
            ->paginate($this->permissionService->perPage($request));

        return $this->successResponse([
            'portfolios' => ProPortfolioResource::collection($portfolios->getCollection()),
            'pagination' => [
                'current_page' => $portfolios->currentPage(),
                'last_page' => $portfolios->lastPage(),
                'per_page' => $portfolios->perPage(),
                'total' => $portfolios->total(),
            ],
        ], 'Liste des portfolios récupérée avec succès.');
    }

    public function show(Request $request, ProPortfolio $proPortfolio)
    {
        $user = Auth::guard('sanctum')->user();
        $proPortfolio->loadMissing(['professionel', 'service.professionel']);

        if (!$this->permissionService->canViewProPortfolio($user, $proPortfolio)) {
            return $this->errorResponse(
                'Portfolio introuvable.',
                ['portfolio' => 'Cette image de portfolio n\'est pas disponible.'],
                404
            );
        }

        return $this->successResponse(
            new ProPortfolioResource($proPortfolio),
            'Portfolio récupéré avec succès.'
        );
    }

    public function store(StoreProPortfolioRequest $request)
    {
        if ($authorization = $this->permissionService->authorizeRole($request->user(), 'professionel')) {
            return $authorization;
        }

        $user = $request->user();
        $service = null;

        if ($request->filled('service_id')) {
            $service = ActivityService::with('professionel')->findOrFail($request->integer('service_id'));

            if (!$this->permissionService->canManageService($user, $service)) {
                return $this->errorResponse(
                    'Action non autorisée.',
                    ['service_id' => 'Vous ne pouvez rattacher ce portfolio qu\'à l\'un de vos services.'],
                    403
                );
            }
        }

        $uploadedImage = $this->uploadImage($request->file('image'), 'pro-portfolios');

        try {
            $portfolio = $user->proPortfolios()->create([
                'service_id' => $service?->id,
                'image' => $uploadedImage,
            ]);
        } catch (\Throwable $e) {
            $this->deleteImage($uploadedImage, 'pro-portfolios');

            return $this->errorResponse(
                'Une erreur s\'est produite lors de la création du portfolio.',
                ['error' => $e->getMessage()],
                500
            );
        }

        $portfolio->loadMissing(['professionel', 'service.professionel']);

        return $this->successResponse(
            new ProPortfolioResource($portfolio),
            'Portfolio créé avec succès.',
            201
        );
    }

    public function update(StoreProPortfolioRequest $request, ProPortfolio $proPortfolio)
    {
        if ($authorization = $this->permissionService->authorizeRole($request->user(), 'professionel')) {
            return $authorization;
        }

        if (!$this->permissionService->canManageProPortfolio($request->user(), $proPortfolio)) {
            return $this->errorResponse(
                'Action non autorisée.',
                ['portfolio' => 'Vous ne pouvez modifier que vos propres portfolios.'],
                403
            );
        }

        $validated = $request->validated();
        $service = null;
        if (array_key_exists('service_id', $validated) && $validated['service_id'] !== null) {
            $service = ActivityService::with('professionel')->findOrFail((int) $validated['service_id']);

            if (!$this->permissionService->canManageService($request->user(), $service)) {
                return $this->errorResponse(
                    'Action non autorisée.',
                    ['service_id' => 'Vous ne pouvez rattacher ce portfolio qu\'à l\'un de vos services.'],
                    403
                );
            }
        }

        $data = [
            'service_id' => array_key_exists('service_id', $validated) ? $validated['service_id'] : $proPortfolio->service_id,
        ];

        $oldImage = $proPortfolio->image;
        $newImage = null;

        try {
            if ($request->hasFile('image')) {
                $newImage = $this->uploadImage($request->file('image'), 'pro-portfolios');
                $data['image'] = $newImage;
            }

            $proPortfolio->update($data);
        } catch (\Throwable $e) {
            if ($newImage) {
                $this->deleteImage($newImage, 'pro-portfolios');
            }

            return $this->errorResponse(
                'Une erreur s\'est produite lors de la mise à jour du portfolio.',
                ['error' => $e->getMessage()],
                500
            );
        }

        if ($newImage && $oldImage) {
            $this->deleteImage($oldImage, 'pro-portfolios');
        }

        $proPortfolio->refresh()->loadMissing(['professionel', 'service.professionel']);

        return $this->successResponse(
            new ProPortfolioResource($proPortfolio),
            'Portfolio mis à jour avec succès.'
        );
    }

    public function switchActive(Request $request, ProPortfolio $proPortfolio)
    {
        if ($authorization = $this->permissionService->authorizeRole($request->user(), 'professionel')) {
            return $authorization;
        }

        if (!$this->permissionService->canManageProPortfolio($request->user(), $proPortfolio)) {
            return $this->errorResponse(
                'Action non autorisée.',
                ['portfolio' => 'Vous ne pouvez modifier que vos propres portfolios.'],
                403
            );
        }

        $proPortfolio->is_active = !$proPortfolio->is_active;
        $proPortfolio->save();
        $proPortfolio->refresh()->loadMissing(['professionel', 'service.professionel']);

        return $this->successResponse(
            new ProPortfolioResource($proPortfolio),
            $proPortfolio->is_active
                ? 'Portfolio activé avec succès.'
                : 'Portfolio désactivé avec succès.'
        );
    }

    public function destroy(Request $request, ProPortfolio $proPortfolio)
    {
        if ($authorization = $this->permissionService->authorizeRole($request->user(), 'professionel')) {
            return $authorization;
        }

        if (!$this->permissionService->canManageProPortfolio($request->user(), $proPortfolio)) {
            return $this->errorResponse(
                'Action non autorisée.',
                ['portfolio' => 'Vous ne pouvez supprimer que vos propres portfolios.'],
                403
            );
        }

        if ($proPortfolio->image) {
            $this->deleteImage($proPortfolio->image, 'pro-portfolios');
        }

        $proPortfolio->delete();

        return $this->noContentSuccessResponse(
            'Portfolio supprimé avec succès.',
            200
        );
    }
}
