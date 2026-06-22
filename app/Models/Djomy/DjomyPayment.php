<?php

namespace App\Models\Djomy;

use App\Models\Activities\Booking;
use Illuminate\Database\Eloquent\Model;

class DjomyPayment extends Model
{
    protected $fillable = [
        'booking_id',
        'merchant_reference',
        'djomy_transaction_id',
        'payment_method',
        'payer_identifier',
        'amount',
        'country_code',
        'status',
        'description',
        'redirect_url',
        'djomy_response',
        'metadata',
    ];

    protected $casts = [
        'djomy_response' => 'array',
        'metadata'       => 'array',
        'amount'         => 'decimal:2',
    ];

    public function isSuccessful(): bool
    {
        return strtoupper($this->status) === 'SUCCESS';
    }

    public function isPending(): bool
    {
        return strtoupper($this->status) === 'PENDING';
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }
}
