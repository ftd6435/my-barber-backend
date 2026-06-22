<?php

namespace App\Http\Controllers;

use App\Http\Resources\WalletTransactionResource;
use App\Models\WalletTransaction;
use App\Services\PermissionService;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class WalletTransactionController extends Controller
{
    use ApiResponses;

    public function __construct(
        private PermissionService $permissionService,
    ) {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $query = WalletTransaction::query()->with(['currency', 'wallet']);

        if ($this->permissionService->isAdmin($user)) {
            if ($request->filled('user_id')) {
                $query->where('user_id', $request->integer('user_id'));
            }
        } else {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('wallet_id')) {
            $query->where('wallet_id', $request->integer('wallet_id'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type')->toString());
        }

        $transactions = $query->latest()->paginate($this->permissionService->perPage($request));

        return $this->successResponse([
            'wallet_transactions' => WalletTransactionResource::collection($transactions->getCollection()),
            'pagination' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ], 'Liste des transactions de wallet récupérée avec succès.');
    }

    public function show(Request $request, WalletTransaction $walletTransaction)
    {
        if (!$this->permissionService->canViewWalletTransaction($request->user(), $walletTransaction)) {
            return $this->errorResponse(
                'Transaction introuvable.',
                ['wallet_transaction' => 'Cette transaction de wallet n\'est pas disponible.'],
                404
            );
        }

        $walletTransaction->loadMissing(['currency', 'wallet']);

        return $this->successResponse(
            new WalletTransactionResource($walletTransaction),
            'Transaction de wallet récupérée avec succès.'
        );
    }
}
