<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'code', 'symbol'])]
class Currency extends Model
{
    public function users()
    {
        return $this->hasMany(User::class, 'default_currency_id');
    }

    public function wallets()
    {
        return $this->hasMany(Wallet::class);
    }

    public function serviceCurrencies()
    {
        return $this->hasMany(\App\Models\Activities\Service::class, 'currency_id');
    }

    public function baseExchangeRates()
    {
        return $this->hasMany(ExchangeRate::class, 'base_currency_id');
    }

    public function quoteExchangeRates()
    {
        return $this->hasMany(ExchangeRate::class, 'quote_currency_id');
    }
}
