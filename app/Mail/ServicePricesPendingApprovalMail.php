<?php

namespace App\Mail;

use App\Models\Activities\Service;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ServicePricesPendingApprovalMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Service $service,
        public int $pricesCount,
        public string $recipientType,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->recipientType === 'admin'
                ? 'Prix de service en attente d\'approbation'
                : 'Vos prix de service sont en attente d\'approbation',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.services.prices-pending-approval',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
