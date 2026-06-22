<?php

namespace App\Mail;

use App\Models\Activities\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingSubmittedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Booking $booking,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nouvelle réservation soumise',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.bookings.submitted',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
