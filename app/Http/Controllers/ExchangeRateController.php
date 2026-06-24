<?php

namespace App\Http\Controllers;

use App\Http\Requests\finance\StoreExchangeRateRequest;
use App\Http\Resources\ExchangeRateResource;
use App\Models\ExchangeRate;
use App\Services\PermissionService;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class ExchangeRateController extends Controller
{
    use ApiResponses;

    public function __construct(
        private PermissionService $permissionService,
    ) {
    }

    public function index(Request $request)
    {
        $query = ExchangeRate::query()->with(['baseCurrency', 'quoteCurrency']);

        if ($request->filled('base_currency_id')) {
            $query->where('base_currency_id', $request->integer('base_currency_id'));
        }

        if ($request->filled('quote_currency_id')) {
            $query->where('quote_currency_id', $request->integer('quote_currency_id'));
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        $rates = $query->latest('fetched_at')->latest()->paginate($this->permissionService->perPage($request));

        return $this->successResponse([
            'exchange_rates' => ExchangeRateResource::collection($rates->getCollection()),
            'pagination' => [
                'current_page' => $rates->currentPage(),
                'last_page' => $rates->lastPage(),
                'per_page' => $rates->perPage(),
                'total' => $rates->total(),
            ],
        ], 'Liste des taux de change récupérée avec succès.');
    }

    public function show(ExchangeRate $exchangeRate)
    {
        $exchangeRate->loadMissing(['baseCurrency', 'quoteCurrency']);

        return $this->successResponse(
            new ExchangeRateResource($exchangeRate),
            'Taux de change récupéré avec succès.'
        );
    }

    public function store(StoreExchangeRateRequest $request)
    {
        if ($authorization = $this->permissionService->authorizeRoles($request->user(), ['super_admin', 'admin'])) {
            return $authorization;
        }

        $data = $request->validated();

        if (($data['is_active'] ?? true) === true) {
            ExchangeRate::query()
                ->where('base_currency_id', $data['base_currency_id'])
                ->where('quote_currency_id', $data['quote_currency_id'])
                ->update(['is_active' => false]);
        }

        $exchangeRate = ExchangeRate::query()->create($data);
        $exchangeRate->loadMissing(['baseCurrency', 'quoteCurrency']);

        return $this->successResponse(
            new ExchangeRateResource($exchangeRate),
            'Taux de change créé avec succès.',
            201
        );
    }

    public function update(StoreExchangeRateRequest $request, ExchangeRate $exchangeRate)
    {
        if ($authorization = $this->permissionService->authorizeRoles($request->user(), ['super_admin', 'admin'])) {
            return $authorization;
        }

        $data = $request->validated();

        if (($data['is_active'] ?? $exchangeRate->is_active) === true) {
            ExchangeRate::query()
                ->where('id', '!=', $exchangeRate->id)
                ->where('base_currency_id', $data['base_currency_id'])
                ->where('quote_currency_id', $data['quote_currency_id'])
                ->update(['is_active' => false]);
        }

        $exchangeRate->update($data);
        $exchangeRate->loadMissing(['baseCurrency', 'quoteCurrency']);

        return $this->successResponse(
            new ExchangeRateResource($exchangeRate),
            'Taux de change mis à jour avec succès.'
        );
    }

    public function switchStatus(Request $request, ExchangeRate $exchangeRate)
    {
        if ($authorization = $this->permissionService->authorizeRoles($request->user(), ['super_admin', 'admin'])) {
            return $authorization;
        }

        $isActive = !$exchangeRate->is_active;

        if ($isActive === true) {
            ExchangeRate::query()
                ->where('id', '!=', $exchangeRate->id)
                ->where('base_currency_id', $exchangeRate->base_currency_id)
                ->where('quote_currency_id', $exchangeRate->quote_currency_id)
                ->update(['is_active' => false]);
        }

        $exchangeRate->update(['is_active' => $isActive]);
        $exchangeRate->loadMissing(['baseCurrency', 'quoteCurrency']);

        return $this->successResponse(
            new ExchangeRateResource($exchangeRate),
            $exchangeRate->is_active
                ? 'Taux de change activé avec succès.'
                : 'Taux de change désactivé avec succès.'
        );
    }

    public function destroy(Request $request, ExchangeRate $exchangeRate)
    {
        if ($authorization = $this->permissionService->authorizeRoles($request->user(), ['super_admin', 'admin'])) {
            return $authorization;
        }

        $exchangeRate->delete();

        return $this->noContentSuccessResponse(
            'Taux de change supprimé avec succès.',
            200
        );
    }
}
