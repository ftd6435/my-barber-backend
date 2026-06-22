<?php

namespace App\Services;

use App\Models\Activities\Booking;
use App\Models\Djomy\DjomyPayment;
use App\Models\Djomy\DjomyPaymentLink;

class BookingPaymentService
{
    public function calculateBookingTotal(Booking $booking): float
    {
        $booking->loadMissing('bookingPrices');

        $subtotal = $booking->bookingPrices->sum(function ($bookingPrice) {
            return (float) $bookingPrice->price * (int) $bookingPrice->number;
        });

        return round((float) $subtotal + (float) $booking->extra_fees, 2);
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

        return round($directPayments + $paymentLinks, 2);
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
}
