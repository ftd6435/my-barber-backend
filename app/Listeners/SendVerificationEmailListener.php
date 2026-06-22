<?php

namespace App\Listeners;

use App\Events\SendVerificationEmailEvent;
use App\Mail\VerifyEmailMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendVerificationEmailListener implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(SendVerificationEmailEvent $event): void
    {
        Mail::to($event->user->email)->send(
            new VerifyEmailMail($event->user, $event->verificationUrl)
        );
    }
}
