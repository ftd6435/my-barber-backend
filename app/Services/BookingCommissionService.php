<?php

namespace App\Services;

use App\Models\BookingCommissionSetting;

class BookingCommissionService
{
    public function getActivePercentage(): float
    {
        return round((float) BookingCommissionSetting::query()
            ->where('is_active', true)
            ->latest()
            ->value('percentage'), 2);
    }

    public function getActiveSetting(): ?BookingCommissionSetting
    {
        return BookingCommissionSetting::query()
            ->where('is_active', true)
            ->latest()
            ->first();
    }
}
