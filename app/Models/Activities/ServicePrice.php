<?php

namespace App\Models\Activities;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['service_id', 'age_range_id', 'price', 'is_approved'])]
class ServicePrice extends Model
{
    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function ageRange()
    {
        return $this->belongsTo(AgeRange::class);
    }
}
