<?php

namespace App\Http\Controllers;

use App\Http\Resources\WalletResource;
use App\Models\Wallet;
use App\Services\PermissionService;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    use ApiResponses;

    public function __construct(
        private PermissionService $permissionService,
    ) {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $query = Wallet::query()->with(['currency', 'user']);

        if ($this->permissionService->isAdmin($user)) {
            if ($request->filled('user_id')) {
                $query->where('user_id', $request->integer('user_id'));
            }
        } else {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('currency_id')) {
            $query->where('currency_id', $request->integer('currency_id'));
        }

        $wallets = $query->latest()->paginate($this->permissionService->perPage($request));

        return $this->successResponse([
            'wallets' => WalletResource::collection($wallets->getCollection()),
            'pagination' => [
                'current_page' => $wallets->currentPage(),
                'last_page' => $wallets->lastPage(),
                'per_page' => $wallets->perPage(),
                'total' => $wallets->total(),
            ],
        ], 'Liste des wallets récupérée avec succès.');
    }

    public function show(Request $request, Wallet $wallet)
    {
        if (!$this->permissionService->canViewWallet($request->user(), $wallet)) {
            return $this->errorResponse(
                'Wallet introuvable.',
                ['wallet' => 'Ce wallet n\'est pas disponible.'],
                404
            );
        }

        $wallet->loadMissing(['currency', 'user']);

        return $this->successResponse(
            new WalletResource($wallet),
            'Wallet récupéré avec succès.'
        );
    }
}
