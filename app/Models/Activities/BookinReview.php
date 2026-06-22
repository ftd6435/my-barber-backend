<?php

namespace App\Models\Activities;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['client_id', 'booking_id', 'professionel_id', 'review', 'rating', 'is_visible'])]
class BookinReview extends Model
{
    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_visible' => 'boolean',
        ];
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function professionel()
    {
        return $this->belongsTo(User::class, 'professionel_id');
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }
}
