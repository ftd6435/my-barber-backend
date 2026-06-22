<?php

namespace App\Services;

use App\Events\SendMessageEvent;
use App\Mail\BookingStatusUpdatedMail;
use App\Mail\BookingSubmittedMail;
use App\Models\Activities\Booking;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class BookingNotificationService
{
    public function notifyProfessionelBookingSubmitted(Booking $booking): void
    {
        $booking->loadMissing(['professionel', 'client', 'service']);

        $professionel = $booking->professionel;

        if ($professionel?->telephone) {
            SendMessageEvent::dispatch(
                $professionel->telephone,
                "Nouvelle réservation {$booking->reference} soumise pour le service {$booking->service?->name}."
            );
        }

        if ($professionel?->email) {
            Mail::to($professionel->email)->send(new BookingSubmittedMail($booking));
        }
    }

    public function notifyClientBookingAccepted(Booking $booking): void
    {
        $this->notifyUserAboutStatusUpdate(
            $booking,
            $booking->client,
            'accepted',
            "Votre réservation {$booking->reference} a été acceptée."
        );
    }

    public function notifyClientBookingRejected(Booking $booking): void
    {
        $this->notifyUserAboutStatusUpdate(
            $booking,
            $booking->client,
            'rejected',
            "Votre réservation {$booking->reference} a été refusée."
        );
    }

    public function notifyProfessionelBookingCancelled(Booking $booking): void
    {
        $this->notifyUserAboutStatusUpdate(
            $booking,
            $booking->professionel,
            'cancelled',
            "La réservation {$booking->reference} a été annulée par le client."
        );
    }

    public function notifyProfessionelBookingCompleted(Booking $booking): void
    {
        $this->notifyUserAboutStatusUpdate(
            $booking,
            $booking->professionel,
            'completed',
            "La réservation {$booking->reference} a été marquée comme terminée par le client."
        );
    }

    private function notifyUserAboutStatusUpdate(
        Booking $booking,
        ?User $recipient,
        string $action,
        string $smsMessage,
    ): void {
        $booking->loadMissing(['professionel', 'client', 'service']);

        if ($recipient?->telephone) {
            SendMessageEvent::dispatch($recipient->telephone, $smsMessage);
        }

        if ($recipient?->email) {
            Mail::to($recipient->email)->send(new BookingStatusUpdatedMail($booking, $action));
        }
    }
}
