<?php

namespace App\Models\Activities;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'description', 'min_age', 'max_age', 'is_active'])]
class AgeRange extends Model
{
    use SoftDeletes;

    public function servicePrices()
    {
        return $this->hasMany(ServicePrice::class, 'age_range_id');
    }

    public function bookingPrices()
    {
        return $this->hasMany(BookingPrice::class, 'age_range_id');
    }
}
