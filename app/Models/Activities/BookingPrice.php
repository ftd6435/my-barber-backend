<?php

namespace App\Models\Activities;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['booking_id', 'age_range_id', 'currency_id', 'number', 'price'])]
class BookingPrice extends Model
{
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function ageRange()
    {
        return $this->belongsTo(AgeRange::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }
}
