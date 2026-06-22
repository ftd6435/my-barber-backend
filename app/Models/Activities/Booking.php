<?php

namespace App\Models\Activities;

use App\Models\User;
use App\Models\Djomy\DjomyPayment;
use App\Models\Djomy\DjomyPaymentLink;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['reference', 'professionel_id', 'client_id', 'service_id', 'booking_date', 'start_time', 'end_time', 'location', 'client_address', 'latitude', 'longitude', 'status', 'payment_status', 'booking_details', 'cancel_reason', 'professionel_comment', 'extra_fees'])]
class Booking extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'booking_details' => 'array',
            'booking_date' => 'date',
            'start_time' => 'time',
            'end_time' => 'time',
        ];
    }

    public function professionel()
    {
        return $this->belongsTo(User::class, 'professionel_id');
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function bookingPrices()
    {
        return $this->hasMany(BookingPrice::class, 'booking_id');
    }

    public function bookingReviews()
    {
        return $this->hasMany(BookinReview::class, 'booking_id');
    }

    public function djomyPayments()
    {
        return $this->hasMany(DjomyPayment::class, 'booking_id');
    }

    public function djomyPaymentLinks()
    {
        return $this->hasMany(DjomyPaymentLink::class, 'booking_id');
    }
}
