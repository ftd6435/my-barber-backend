<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCurrencyRequest;
use App\Http\Resources\CurrencyResource;
use App\Models\Currency;
use App\Services\PermissionService;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    use ApiResponses;

    public function __construct(
        private PermissionService $permissionService,
    ) {}

    /**
     * Get a list of all currencies. This doesn't require any authentication.
     */
    public function index(Request $request)
    {
        $currencies = Currency::query()
            ->orderBy('name')
            ->paginate($this->permissionService->perPage($request));

        return $this->successResponse([
            'currencies' => CurrencyResource::collection($currencies->getCollection()),
            'pagination' => [
                'current_page' => $currencies->currentPage(),
                'last_page' => $currencies->lastPage(),
                'per_page' => $currencies->perPage(),
                'total' => $currencies->total(),
            ],
        ], 'Liste des devises récupérée avec succès.');
    }

    public function show(Currency $currency)
    {
        return $this->successResponse(
            new CurrencyResource($currency),
            'Devise récupérée avec succès.'
        );
    }

    public function store(StoreCurrencyRequest $request)
    {
        if ($authorization = $this->permissionService->authorizeRoles($request->user(), ['super_admin', 'admin'])) {
            return $authorization;
        }

        $currency = Currency::create($request->validated());

        return $this->successResponse(
            new CurrencyResource($currency),
            'Devise créée avec succès.',
            201
        );
    }

    public function update(StoreCurrencyRequest $request, Currency $currency)
    {
        if ($authorization = $this->permissionService->authorizeRoles($request->user(), ['super_admin', 'admin'])) {
            return $authorization;
        }

        $currency->update($request->validated());

        return $this->successResponse(
            new CurrencyResource($currency),
            'Devise mise à jour avec succès.'
        );
    }

    public function destroy(Request $request, Currency $currency)
    {
        if ($authorization = $this->permissionService->authorizeRoles($request->user(), ['super_admin', 'admin'])) {
            return $authorization;
        }

        $currency->delete();

        return $this->noContentSuccessResponse(
            'Devise supprimée avec succès.',
            200
        );
    }
}
