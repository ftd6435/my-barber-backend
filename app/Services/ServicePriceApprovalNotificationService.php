<?php

namespace App\Services;

use App\Mail\ServicePricesPendingApprovalMail;
use App\Models\Activities\Service;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class ServicePriceApprovalNotificationService
{
    public function sendPendingApprovalNotifications(Service $service, int $pricesCount): void
    {
        $service->loadMissing(['professionel', 'salon']);

        if ($service->professionel?->email) {
            Mail::to($service->professionel->email)->queue(
                new ServicePricesPendingApprovalMail($service, $pricesCount, 'owner')
            );
        }

        $adminEmails = User::query()
            ->whereIn('role', ['super_admin', 'admin'])
            ->where('is_active', true)
            ->whereNotNull('email')
            ->pluck('email')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($adminEmails !== []) {
            Mail::to($adminEmails)->queue(
                new ServicePricesPendingApprovalMail($service, $pricesCount, 'admin')
            );
        }
    }
}
