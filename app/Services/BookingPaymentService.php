<?php

namespace App\Services;

use App\Models\Activities\Booking;
use App\Models\Djomy\DjomyPayment;
use App\Models\Djomy\DjomyPaymentLink;
use App\Models\User;
use App\Models\Wallet;
use RuntimeException;

class BookingPaymentService
{
    public function __construct(
        private WalletService $walletService,
    ) {
    }

    public function calculateBookingTotal(Booking $booking): float
    {
        return round((float) $booking->client_total_amount, 2);
    }

    public function calculatePaidAmount(Booking $booking): float
    {
        $directPayments = (float) DjomyPayment::query()
            ->where('booking_id', $booking->id)
            ->where('status', 'SUCCESS')
            ->sum('amount');

        $paymentLinks = (float) DjomyPaymentLink::query()
            ->where('booking_id', $booking->id)
            ->where('status', 'SUCCESS')
            ->sum('paid_amount');

        return round(max(0, $directPayments + $paymentLinks - (float) $booking->client_refunded_amount), 2);
    }

    public function calculateRemainingAmount(Booking $booking): float
    {
        return round(max(0, $this->calculateBookingTotal($booking) - $this->calculatePaidAmount($booking)), 2);
    }

    public function syncBookingPaymentStatus(Booking $booking): Booking
    {
        $paidAmount = $this->calculatePaidAmount($booking);
        $totalAmount = $this->calculateBookingTotal($booking);

        $paymentStatus = 'pending';

        if ($paidAmount > 0 && $paidAmount < $totalAmount) {
            $paymentStatus = 'partial';
        } elseif ($paidAmount >= $totalAmount && $totalAmount > 0) {
            $paymentStatus = 'completed';
        }

        if ($booking->payment_status !== $paymentStatus) {
            $booking->update(['payment_status' => $paymentStatus]);
        }

        return $booking->refresh();
    }

    public function refreshBookingMonetarySnapshot(Booking $booking): Booking
    {
        $booking->loadMissing('bookingPrices');

        $serviceSubtotal = round((float) $booking->bookingPrices->sum(function ($bookingPrice) {
            return (float) $bookingPrice->price * (int) $bookingPrice->number;
        }), 2);

        $serviceTotal = round($serviceSubtotal + (float) $booking->extra_fees, 2);
        $exchangeRate = max(0.00000001, (float) $booking->service_to_client_exchange_rate);
        $clientTotal = round($serviceTotal * $exchangeRate, 2);
        $platformFeeAmount = round($serviceTotal * ((float) $booking->platform_fee_percentage / 100), 2);
        $professionelNetAmount = round(max(0, $serviceTotal - $platformFeeAmount), 2);

        $booking->update([
            'service_subtotal_amount' => $serviceSubtotal,
            'service_total_amount' => $serviceTotal,
            'client_total_amount' => $clientTotal,
            'settlement_total_amount' => $serviceTotal,
            'platform_fee_amount' => $platformFeeAmount,
            'professionel_net_amount' => $professionelNetAmount,
        ]);

        return $this->syncBookingPaymentStatus($booking->fresh());
    }

    public function applySuccessfulDirectPayment(DjomyPayment $payment): void
    {
        if (!$payment->booking || $payment->is_wallet_applied || !$payment->isSuccessful()) {
            return;
        }

        $this->applySuccessfulBookingPayment(
            $payment->booking,
            (float) $payment->amount,
            'DjomyPayment',
            $payment->id
        );

        $payment->update([
            'is_wallet_applied' => true,
            'wallet_applied_at' => now(),
        ]);
    }

    public function applySuccessfulPaymentLink(DjomyPaymentLink $paymentLink): void
    {
        if (!$paymentLink->booking || $paymentLink->is_wallet_applied || strtoupper($paymentLink->status) !== 'SUCCESS') {
            return;
        }

        $amount = round((float) ($paymentLink->paid_amount ?? $paymentLink->amount_to_pay ?? 0), 2);

        if ($amount <= 0) {
            return;
        }

        $this->applySuccessfulBookingPayment(
            $paymentLink->booking,
            $amount,
            'DjomyPaymentLink',
            $paymentLink->id
        );

        $paymentLink->update([
            'is_wallet_applied' => true,
            'wallet_applied_at' => now(),
        ]);
    }

    public function refundBookingToClientWallet(Booking $booking, string $reason): void
    {
        $booking->loadMissing(['client', 'professionel']);

        $refundableClientAmount = $this->calculateRefundableClientAmount($booking);

        if ($refundableClientAmount <= 0) {
            $this->syncBookingPaymentStatus($booking);
            return;
        }

        $clientWallet = $this->walletForUserAndCurrency($booking->client, (int) $booking->client_currency_id);
        $proWallet = $this->walletForUserAndCurrency($booking->professionel, (int) $booking->settlement_currency_id);
        $settlementAmount = $this->convertClientAmountToSettlement($booking, $refundableClientAmount);

        if ($settlementAmount > 0) {
            $availableHeldToReverse = round((float) $proWallet->held_balance, 2);
            $reversibleAmount = round(min($settlementAmount, $availableHeldToReverse), 2);

            if ($reversibleAmount > 0) {
                $this->walletService->debitHeld(
                    $proWallet,
                    $reversibleAmount,
                    'booking_refund_reversal',
                    'Remboursement au client après annulation ou refus de la réservation.',
                    ['reason' => $reason],
                    Booking::class,
                    $booking->id
                );
            }
        }

        $this->walletService->creditAvailable(
            $clientWallet,
            $refundableClientAmount,
            'booking_refund_credit',
            'Crédit du remboursement dans le wallet client.',
            ['reason' => $reason],
            Booking::class,
            $booking->id
        );

        $booking->update([
            'client_refunded_amount' => round((float) $booking->client_refunded_amount + $refundableClientAmount, 2),
        ]);

        $this->syncBookingPaymentStatus($booking->fresh());
    }

    public function releaseBookingFundsToProfessionel(Booking $booking): void
    {
        $booking = $this->refreshBookingMonetarySnapshot($booking);
        $booking->loadMissing('professionel');

        $settlementAmount = $this->calculateHeldSettlementAmount($booking);

        if ($settlementAmount <= 0) {
            return;
        }

        $wallet = $this->walletForUserAndCurrency($booking->professionel, (int) $booking->settlement_currency_id);
        $availableHeldBalance = round((float) $wallet->held_balance, 2);
        $grossReleasableAmount = round(min($settlementAmount, $availableHeldBalance), 2);

        if ($grossReleasableAmount <= 0) {
            return;
        }

        $platformFeeAmount = round(min((float) $booking->platform_fee_amount, $grossReleasableAmount), 2);
        $netAmount = round(max(0, min((float) $booking->professionel_net_amount, $grossReleasableAmount - $platformFeeAmount)), 2);

        if ($platformFeeAmount > 0) {
            $this->walletService->debitHeld(
                $wallet,
                $platformFeeAmount,
                'platform_fee_deduction',
                'Déduction de la commission de la plateforme après finalisation de la réservation.',
                ['percentage' => (float) $booking->platform_fee_percentage],
                Booking::class,
                $booking->id
            );
        }

        if ($netAmount > 0) {
            $this->walletService->releaseHeldToAvailable(
                $wallet->fresh(),
                $netAmount,
                'booking_payment_release',
                'Libération du montant net après déduction de la commission de la plateforme.',
                [
                    'platform_fee_amount' => $platformFeeAmount,
                    'platform_fee_percentage' => (float) $booking->platform_fee_percentage,
                ],
                Booking::class,
                $booking->id
            );
        }
    }

    private function applySuccessfulBookingPayment(Booking $booking, float $clientAmount, string $referenceType, int $referenceId): void
    {
        $booking->loadMissing(['client', 'professionel']);

        if (in_array($booking->status, ['rejected', 'cancelled'], true)) {
            $wallet = $this->walletForUserAndCurrency($booking->client, (int) $booking->client_currency_id);

            $this->walletService->creditAvailable(
                $wallet,
                $clientAmount,
                'booking_refund_credit',
                'Paiement reçu après annulation ou refus de la réservation.',
                [],
                $referenceType,
                $referenceId
            );

            $booking->update([
                'client_refunded_amount' => round((float) $booking->client_refunded_amount + $clientAmount, 2),
            ]);

            $this->syncBookingPaymentStatus($booking->fresh());

            return;
        }

        $settlementAmount = $this->convertClientAmountToSettlement($booking, $clientAmount);
        $wallet = $this->walletForUserAndCurrency($booking->professionel, (int) $booking->settlement_currency_id);

        if ($settlementAmount > 0) {
            $this->walletService->creditHeld(
                $wallet,
                $settlementAmount,
                'booking_payment_hold',
                'Fonds bloqués pour une réservation payée.',
                [
                    'client_amount' => $clientAmount,
                    'exchange_rate' => (float) $booking->service_to_client_exchange_rate,
                ],
                $referenceType,
                $referenceId
            );
        }

        $this->syncBookingPaymentStatus($booking->fresh());
    }

    private function walletForUserAndCurrency(?User $user, int $currencyId): Wallet
    {
        if (!$user) {
            throw new RuntimeException('Utilisateur introuvable pour cette opération de wallet.');
        }

        return $this->walletService->ensureWallet($user, $currencyId);
    }

    private function calculateRefundableClientAmount(Booking $booking): float
    {
        return round(max(0, $this->calculateGrossSuccessfulClientAmount($booking) - (float) $booking->client_refunded_amount), 2);
    }

    private function calculateHeldSettlementAmount(Booking $booking): float
    {
        return round($this->convertClientAmountToSettlement($booking, $this->calculateRefundableClientAmount($booking)), 2);
    }

    private function calculateGrossSuccessfulClientAmount(Booking $booking): float
    {
        $directPayments = (float) DjomyPayment::query()
            ->where('booking_id', $booking->id)
            ->where('status', 'SUCCESS')
            ->where('is_wallet_applied', true)
            ->sum('amount');

        $paymentLinks = (float) DjomyPaymentLink::query()
            ->where('booking_id', $booking->id)
            ->where('status', 'SUCCESS')
            ->where('is_wallet_applied', true)
            ->sum('paid_amount');

        return round($directPayments + $paymentLinks, 2);
    }

    private function convertClientAmountToSettlement(Booking $booking, float $clientAmount): float
    {
        $rate = max(0.00000001, (float) $booking->service_to_client_exchange_rate);

        return round($clientAmount / $rate, 2);
    }
}
