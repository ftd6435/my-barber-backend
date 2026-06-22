<?php

namespace App\Mail;

use App\Models\Activities\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingStatusUpdatedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Booking $booking,
        public string $action,
    ) {
    }

    public function envelope(): Envelope
    {
        $subjects = [
            'accepted' => 'Réservation acceptée',
            'rejected' => 'Réservation refusée',
            'cancelled' => 'Réservation annulée',
            'completed' => 'Réservation terminée',
        ];

        return new Envelope(
            subject: $subjects[$this->action] ?? 'Mise à jour de réservation',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.bookings.status-updated',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
