<?php

namespace App\Services;

use App\Models\ExchangeRate;
use RuntimeException;

class CurrencyConversionService
{
    public function getRate(int $baseCurrencyId, int $quoteCurrencyId): float
    {
        if ($baseCurrencyId === $quoteCurrencyId) {
            return 1.0;
        }

        $directRate = ExchangeRate::query()
            ->where('base_currency_id', $baseCurrencyId)
            ->where('quote_currency_id', $quoteCurrencyId)
            ->where('is_active', true)
            ->latest('fetched_at')
            ->latest('id')
            ->first();

        if ($directRate) {
            return (float) $directRate->rate;
        }

        $inverseRate = ExchangeRate::query()
            ->where('base_currency_id', $quoteCurrencyId)
            ->where('quote_currency_id', $baseCurrencyId)
            ->where('is_active', true)
            ->latest('fetched_at')
            ->latest('id')
            ->first();

        if ($inverseRate && (float) $inverseRate->rate > 0) {
            return round(1 / (float) $inverseRate->rate, 8);
        }

        throw new RuntimeException('Aucun taux de change actif n\'est disponible pour cette paire de devises.');
    }

    public function convert(float $amount, int $baseCurrencyId, int $quoteCurrencyId): array
    {
        $rate = $this->getRate($baseCurrencyId, $quoteCurrencyId);

        return [
            'rate' => $rate,
            'amount' => round($amount * $rate, 2),
        ];
    }
}
