<?php

namespace App\Models\Activities;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['booking_id', 'age_range_id', 'number', 'price'])]
class BookingPrice extends Model
{
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function ageRange()
    {
        return $this->belongsTo(AgeRange::class);
    }
}
