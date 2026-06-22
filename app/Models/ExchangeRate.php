<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    protected $fillable = [
        'base_currency_id',
        'quote_currency_id',
        'rate',
        'source',
        'is_active',
        'fetched_at',
    ];

    protected $casts = [
        'rate' => 'decimal:8',
        'is_active' => 'boolean',
        'fetched_at' => 'datetime',
    ];

    public function baseCurrency()
    {
        return $this->belongsTo(Currency::class, 'base_currency_id');
    }

    public function quoteCurrency()
    {
        return $this->belongsTo(Currency::class, 'quote_currency_id');
    }
}
