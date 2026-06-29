<?php

namespace App\Models\Activities;

use App\Models\Currency;
use App\Models\User;
use App\Models\Djomy\DjomyPayment;
use App\Models\Djomy\DjomyPaymentLink;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['reference', 'professionel_id', 'client_id', 'service_id', 'service_currency_id', 'client_currency_id', 'settlement_currency_id', 'booking_date', 'start_time', 'end_time', 'location', 'client_address', 'latitude', 'longitude', 'status', 'payment_status', 'service_to_client_exchange_rate', 'service_subtotal_amount', 'service_total_amount', 'client_total_amount', 'settlement_total_amount', 'client_refunded_amount', 'platform_fee_percentage', 'platform_fee_amount', 'professionel_net_amount', 'booking_details', 'cancel_reason', 'professionel_comment', 'extra_fees'])]
class Booking extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'booking_details' => 'array',
            'booking_date' => 'date',
            'start_time'   => 'datetime:H:i:s',
            'end_time'     => 'datetime:H:i:s',
            'service_to_client_exchange_rate' => 'decimal:8',
            'service_subtotal_amount' => 'decimal:2',
            'service_total_amount' => 'decimal:2',
            'client_total_amount' => 'decimal:2',
            'settlement_total_amount' => 'decimal:2',
            'client_refunded_amount' => 'decimal:2',
            'platform_fee_percentage' => 'decimal:2',
            'platform_fee_amount' => 'decimal:2',
            'professionel_net_amount' => 'decimal:2',
            'extra_fees' => 'decimal:2',
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

    public function serviceCurrency()
    {
        return $this->belongsTo(Currency::class, 'service_currency_id');
    }

    public function clientCurrency()
    {
        return $this->belongsTo(Currency::class, 'client_currency_id');
    }

    public function settlementCurrency()
    {
        return $this->belongsTo(Currency::class, 'settlement_currency_id');
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
