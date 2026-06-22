<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WalletService
{
    public function ensureWallet(User $user, int $currencyId): Wallet
    {
        return Wallet::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'currency_id' => $currencyId,
            ],
            [
                'available_balance' => 0,
                'held_balance' => 0,
                'is_locked' => false,
            ]
        );
    }

    public function creditAvailable(Wallet $wallet, float $amount, string $type, ?string $description = null, array $meta = [], ?string $referenceType = null, ?int $referenceId = null): WalletTransaction
    {
        return $this->recordBalanceMutation($wallet, $amount, 'credit', 'available', $type, $description, $meta, $referenceType, $referenceId);
    }

    public function debitAvailable(Wallet $wallet, float $amount, string $type, ?string $description = null, array $meta = [], ?string $referenceType = null, ?int $referenceId = null): WalletTransaction
    {
        return $this->recordBalanceMutation($wallet, $amount, 'debit', 'available', $type, $description, $meta, $referenceType, $referenceId);
    }

    public function creditHeld(Wallet $wallet, float $amount, string $type, ?string $description = null, array $meta = [], ?string $referenceType = null, ?int $referenceId = null): WalletTransaction
    {
        return $this->recordBalanceMutation($wallet, $amount, 'credit', 'held', $type, $description, $meta, $referenceType, $referenceId);
    }

    public function debitHeld(Wallet $wallet, float $amount, string $type, ?string $description = null, array $meta = [], ?string $referenceType = null, ?int $referenceId = null): WalletTransaction
    {
        return $this->recordBalanceMutation($wallet, $amount, 'debit', 'held', $type, $description, $meta, $referenceType, $referenceId);
    }

    public function holdFromAvailable(Wallet $wallet, float $amount, string $type, ?string $description = null, array $meta = [], ?string $referenceType = null, ?int $referenceId = null): void
    {
        DB::transaction(function () use ($wallet, $amount, $type, $description, $meta, $referenceType, $referenceId) {
            $this->debitAvailable($wallet, $amount, $type, $description, $meta, $referenceType, $referenceId);
            $this->creditHeld($wallet->fresh(), $amount, $type, $description, $meta, $referenceType, $referenceId);
        });
    }

    public function releaseHeldToAvailable(Wallet $wallet, float $amount, string $type, ?string $description = null, array $meta = [], ?string $referenceType = null, ?int $referenceId = null): void
    {
        DB::transaction(function () use ($wallet, $amount, $type, $description, $meta, $referenceType, $referenceId) {
            $this->debitHeld($wallet, $amount, $type, $description, $meta, $referenceType, $referenceId);
            $this->creditAvailable($wallet->fresh(), $amount, $type, $description, $meta, $referenceType, $referenceId);
        });
    }

    private function recordBalanceMutation(Wallet $wallet, float $amount, string $direction, string $balanceType, string $type, ?string $description, array $meta, ?string $referenceType, ?int $referenceId): WalletTransaction
    {
        if ($amount <= 0) {
            throw new RuntimeException('Le montant doit être strictement positif.');
        }

        return DB::transaction(function () use ($wallet, $amount, $direction, $balanceType, $type, $description, $meta, $referenceType, $referenceId) {
            /** @var Wallet $freshWallet */
            $freshWallet = Wallet::query()->lockForUpdate()->findOrFail($wallet->id);

            if ($freshWallet->is_locked) {
                throw new RuntimeException('Ce wallet est verrouillé et ne peut pas être utilisé.');
            }

            $field = $balanceType === 'held' ? 'held_balance' : 'available_balance';
            $before = round((float) $freshWallet->{$field}, 2);
            $after = $direction === 'credit'
                ? round($before + $amount, 2)
                : round($before - $amount, 2);

            if ($after < 0) {
                throw new RuntimeException('Solde insuffisant pour effectuer cette opération.');
            }

            $freshWallet->update([$field => $after]);

            return WalletTransaction::query()->create([
                'wallet_id' => $freshWallet->id,
                'user_id' => $freshWallet->user_id,
                'currency_id' => $freshWallet->currency_id,
                'type' => $type,
                'direction' => $direction,
                'balance_type' => $balanceType,
                'status' => 'completed',
                'amount' => $amount,
                'balance_before' => $before,
                'balance_after' => $after,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'description' => $description,
                'meta' => $meta === [] ? null : $meta,
            ]);
        });
    }
}
