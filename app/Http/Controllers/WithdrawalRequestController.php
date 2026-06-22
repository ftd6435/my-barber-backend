<?php

namespace App\Http\Controllers;

use App\Http\Requests\finance\ProcessWithdrawalRequest;
use App\Http\Requests\finance\StoreWithdrawalRequest;
use App\Http\Resources\WithdrawalRequestResource;
use App\Models\Wallet;
use App\Models\WithdrawalRequest;
use App\Services\PermissionService;
use App\Services\WalletService;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WithdrawalRequestController extends Controller
{
    use ApiResponses;

    public function __construct(
        private PermissionService $permissionService,
        private WalletService $walletService,
    ) {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $query = WithdrawalRequest::query()->with(['wallet.currency', 'currency', 'user', 'processedBy']);

        if ($this->permissionService->isAdmin($user)) {
            if ($request->filled('user_id')) {
                $query->where('user_id', $request->integer('user_id'));
            }
        } else {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        $withdrawals = $query->latest()->paginate($this->permissionService->perPage($request));

        return $this->successResponse([
            'withdrawal_requests' => WithdrawalRequestResource::collection($withdrawals->getCollection()),
            'pagination' => [
                'current_page' => $withdrawals->currentPage(),
                'last_page' => $withdrawals->lastPage(),
                'per_page' => $withdrawals->perPage(),
                'total' => $withdrawals->total(),
            ],
        ], 'Liste des demandes de retrait récupérée avec succès.');
    }

    public function show(Request $request, WithdrawalRequest $withdrawalRequest)
    {
        if (!$this->permissionService->canViewWithdrawalRequest($request->user(), $withdrawalRequest)) {
            return $this->errorResponse(
                'Demande de retrait introuvable.',
                ['withdrawal_request' => 'Cette demande de retrait n\'est pas disponible.'],
                404
            );
        }

        $withdrawalRequest->loadMissing(['wallet.currency', 'currency', 'user', 'processedBy']);

        return $this->successResponse(
            new WithdrawalRequestResource($withdrawalRequest),
            'Demande de retrait récupérée avec succès.'
        );
    }

    public function store(StoreWithdrawalRequest $request)
    {
        $wallet = Wallet::query()->with('currency')->findOrFail($request->integer('wallet_id'));

        if (!$this->permissionService->canManageWallet($request->user(), $wallet)) {
            return $this->errorResponse(
                'Action non autorisée.',
                ['wallet' => 'Vous ne pouvez demander un retrait que depuis votre propre wallet.'],
                403
            );
        }

        if ($wallet->is_locked) {
            return $this->errorResponse(
                'Wallet verrouillé.',
                ['wallet' => 'Ce wallet est verrouillé et ne peut pas initier de retrait.'],
                422
            );
        }

        $amount = round((float) $request->validated('amount'), 2);

        if ($amount > (float) $wallet->available_balance) {
            return $this->errorResponse(
                'Solde insuffisant.',
                ['amount' => 'Le montant demandé dépasse le solde disponible du wallet.'],
                422
            );
        }

        $withdrawalRequest = DB::transaction(function () use ($request, $wallet, $amount) {
            $withdrawalRequest = WithdrawalRequest::query()->create([
                'wallet_id' => $wallet->id,
                'user_id' => $request->user()->id,
                'currency_id' => $wallet->currency_id,
                'amount' => $amount,
                'status' => 'pending',
                'destination_details' => $request->validated('destination_details'),
                'comment' => $request->validated('comment'),
            ]);

            $this->walletService->holdFromAvailable(
                $wallet,
                $amount,
                'withdrawal_hold',
                'Mise en attente d\'un retrait.',
                [],
                WithdrawalRequest::class,
                $withdrawalRequest->id
            );

            return $withdrawalRequest;
        });

        $withdrawalRequest->loadMissing(['wallet.currency', 'currency', 'user', 'processedBy']);

        return $this->successResponse(
            new WithdrawalRequestResource($withdrawalRequest),
            'Demande de retrait créée avec succès.',
            201
        );
    }

    public function process(ProcessWithdrawalRequest $request, WithdrawalRequest $withdrawalRequest)
    {
        if ($authorization = $this->permissionService->authorizeRoles($request->user(), ['super_admin', 'admin'])) {
            return $authorization;
        }

        if ($withdrawalRequest->status !== 'pending') {
            return $this->errorResponse(
                'Action impossible.',
                ['status' => 'Seules les demandes de retrait en attente peuvent être traitées.'],
                422
            );
        }

        $wallet = $withdrawalRequest->wallet()->firstOrFail();
        $status = $request->validated('status');

        DB::transaction(function () use ($request, $withdrawalRequest, $wallet, $status) {
            if ($status === 'rejected') {
                $this->walletService->releaseHeldToAvailable(
                    $wallet,
                    (float) $withdrawalRequest->amount,
                    'withdrawal_release',
                    'Restitution du retrait refusé.',
                    [],
                    WithdrawalRequest::class,
                    $withdrawalRequest->id
                );
            } else {
                $this->walletService->debitHeld(
                    $wallet,
                    (float) $withdrawalRequest->amount,
                    'withdrawal_debit',
                    $status === 'paid' ? 'Retrait payé.' : 'Retrait approuvé.',
                    [],
                    WithdrawalRequest::class,
                    $withdrawalRequest->id
                );
            }

            $withdrawalRequest->update([
                'status' => $status,
                'comment' => $request->validated('comment') ?? $withdrawalRequest->comment,
                'processed_by' => $request->user()->id,
                'processed_at' => now(),
            ]);
        });

        $withdrawalRequest->loadMissing(['wallet.currency', 'currency', 'user', 'processedBy']);

        return $this->successResponse(
            new WithdrawalRequestResource($withdrawalRequest),
            'Demande de retrait traitée avec succès.'
        );
    }

    public function cancel(Request $request, WithdrawalRequest $withdrawalRequest)
    {
        if (!$this->permissionService->canManageWithdrawalRequest($request->user(), $withdrawalRequest)) {
            return $this->errorResponse(
                'Action non autorisée.',
                ['withdrawal_request' => 'Vous ne pouvez annuler que vos propres demandes de retrait.'],
                403
            );
        }

        if ($withdrawalRequest->status !== 'pending') {
            return $this->errorResponse(
                'Action impossible.',
                ['status' => 'Seules les demandes de retrait en attente peuvent être annulées.'],
                422
            );
        }

        $wallet = $withdrawalRequest->wallet()->firstOrFail();

        DB::transaction(function () use ($withdrawalRequest, $wallet) {
            $this->walletService->releaseHeldToAvailable(
                $wallet,
                (float) $withdrawalRequest->amount,
                'withdrawal_release',
                'Restitution du retrait annulé.',
                [],
                WithdrawalRequest::class,
                $withdrawalRequest->id
            );

            $withdrawalRequest->update([
                'status' => 'cancelled',
                'processed_at' => now(),
            ]);
        });

        $withdrawalRequest->loadMissing(['wallet.currency', 'currency', 'user', 'processedBy']);

        return $this->successResponse(
            new WithdrawalRequestResource($withdrawalRequest),
            'Demande de retrait annulée avec succès.'
        );
    }
}
