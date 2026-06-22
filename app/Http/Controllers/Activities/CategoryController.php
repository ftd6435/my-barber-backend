<?php

namespace App\Http\Controllers\Activities;

use App\Http\Controllers\Controller;
use App\Http\Requests\activities\StoreCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Activities\Category;
use App\Services\PermissionService;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class CategoryController extends Controller
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
        $query = Category::query()->with(['createdBy', 'updatedBy']);

        $categories = $query->orderBy('name')->paginate($this->permissionService->perPage($request));

        return $this->successResponse([
            'categories' => CategoryResource::collection($categories->getCollection()),
            'pagination' => [
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
            ],
        ], 'Liste des catégories récupérée avec succès.');
    }

    public function show(Category $category)
    {
        $category->loadMissing(['createdBy', 'updatedBy']);

        return $this->successResponse(
            new CategoryResource($category),
            'Catégorie récupérée avec succès.'
        );
    }

    public function store(StoreCategoryRequest $request)
    {
        if ($authorization = $this->permissionService->authorizeRoles($request->user(), ['super_admin', 'admin'])) {
            return $authorization;
        }

        $category = Category::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        $category->loadMissing(['createdBy', 'updatedBy']);

        return $this->successResponse(
            new CategoryResource($category),
            'Catégorie créée avec succès.',
            201
        );
    }

    public function update(StoreCategoryRequest $request, Category $category)
    {
        if ($authorization = $this->permissionService->authorizeRoles($request->user(), ['super_admin', 'admin'])) {
            return $authorization;
        }

        $category->update([
            ...$request->validated(),
            'updated_by' => $request->user()->id,
        ]);
        $category->loadMissing(['createdBy', 'updatedBy']);

        return $this->successResponse(
            new CategoryResource($category),
            'Catégorie mise à jour avec succès.'
        );
    }

    public function switchStatus(Request $request, Category $category)
    {
        if ($authorization = $this->permissionService->authorizeRoles($request->user(), ['super_admin', 'admin'])) {
            return $authorization;
        }

        $category->is_active = !$category->is_active;
        $category->updated_by = $request->user()->id;
        $category->save();
        $category->loadMissing(['createdBy', 'updatedBy']);

        return $this->successResponse(
            new CategoryResource($category),
            $category->is_active
                ? 'Catégorie activée avec succès.'
                : 'Catégorie désactivée avec succès.'
        );
    }

    public function destroy(Request $request, Category $category)
    {
        if ($authorization = $this->permissionService->authorizeRoles($request->user(), ['super_admin', 'admin'])) {
            return $authorization;
        }

        $category->delete();

        return $this->noContentSuccessResponse(
            'Catégorie supprimée avec succès.',
            200
        );
    }
}
