<?php

namespace App\Models\Djomy;

use App\Models\Activities\Booking;
use Illuminate\Database\Eloquent\Model;

class DjomyPaymentLink extends Model
{
    protected $fillable = [
        'booking_id',
        'djomy_reference',
        'merchant_reference',
        'link_name',
        'link_url',
        'amount_to_pay',
        'paid_amount',
        'country_code',
        'usage_type',
        'usage_limit',
        'status',
        'expires_at',
        'description',
        'allowed_payment_methods',
        'djomy_response',
        'metadata',
    ];

    protected $casts = [
        'djomy_response'          => 'array',
        'metadata'                => 'array',
        'allowed_payment_methods' => 'array',
        'amount_to_pay'           => 'decimal:2',
        'paid_amount'             => 'decimal:2',
        'expires_at'              => 'datetime',
    ];

    public function isActive(): bool
    {
        return $this->status === 'ACTIVE';
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }
}
