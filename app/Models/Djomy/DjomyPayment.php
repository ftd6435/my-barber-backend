<?php

namespace App\Models\Djomy;

use App\Models\Activities\Booking;
use App\Models\Currency;
use Illuminate\Database\Eloquent\Model;

class DjomyPayment extends Model
{
    protected $fillable = [
        'booking_id',
        'currency_id',
        'merchant_reference',
        'djomy_transaction_id',
        'payment_method',
        'payer_identifier',
        'amount',
        'country_code',
        'status',
        'is_wallet_applied',
        'wallet_applied_at',
        'description',
        'redirect_url',
        'djomy_response',
        'metadata',
    ];

    protected $casts = [
        'djomy_response' => 'array',
        'metadata'       => 'array',
        'amount'         => 'decimal:2',
        'is_wallet_applied' => 'boolean',
        'wallet_applied_at' => 'datetime',
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

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function isFailed(): bool
    {
        return strtoupper($this->status) === 'FAILED';
    }

    public function scopeByMerchantReference($query, string $reference)
    {
        return $query->where('merchant_reference', $reference);
    }

    public function scopeByTransactionId($query, string $transactionId)
    {
        return $query->where('djomy_transaction_id', $transactionId);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'PENDING');
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'SUCCESS');
    }
}
