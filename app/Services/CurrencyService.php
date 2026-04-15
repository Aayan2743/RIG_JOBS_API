<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class CurrencyService
{
    public function getUsdToInr()
    {
        return Cache::remember('usd_to_inr', 3600, function () {
            $response = Http::get('https://api.exchangerate-api.com/v4/latest/USD');

            if ($response->successful()) {
                return $response->json()['rates']['INR'] ?? 83;
            }

            return 83; // fallback
        });
    }
}
