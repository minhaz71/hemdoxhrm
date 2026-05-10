<?php

namespace App\Services;

class CurrencyService
{
    private const CACHE_KEY = 'currency_config';

    // Presets used by the admin settings page.
    // symbol_space controls whether a space separates symbol and amount for "after" position.
    public static array $presets = [
        'USD' => ['name' => 'US Dollar',        'symbol' => '$',    'position' => 'before', 'decimal' => '.', 'thousand' => ','],
        'BDT' => ['name' => 'Bangladeshi Taka', 'symbol' => '৳',   'position' => 'before', 'decimal' => '.', 'thousand' => ','],
        'AED' => ['name' => 'UAE Dirham',        'symbol' => 'AED',  'position' => 'after',  'decimal' => '.', 'thousand' => ','],
        'INR' => ['name' => 'Indian Rupee',      'symbol' => '₹',   'position' => 'before', 'decimal' => '.', 'thousand' => ','],
        'EUR' => ['name' => 'Euro',              'symbol' => '€',   'position' => 'after',  'decimal' => ',', 'thousand' => '.'],
        'GBP' => ['name' => 'British Pound',     'symbol' => '£',   'position' => 'before', 'decimal' => '.', 'thousand' => ','],
    ];

    // Config is memoised per request — SettingService already caches across requests.
    private ?array $config = null;

    public function __construct(private readonly SettingService $settings) {}

    public function getConfig(): array
    {
        if ($this->config !== null) {
            return $this->config;
        }

        return $this->config = [
            'code'     => $this->settings->get('currency_code',      'USD'),
            'symbol'   => $this->settings->get('currency_symbol',    '$'),
            'position' => $this->settings->get('currency_position',  'before'),
            'decimal'  => $this->settings->get('decimal_separator',  '.'),
            'thousand' => $this->settings->get('thousand_separator', ','),
        ];
    }

    public function format(float|int|string $amount, int $decimals = 2): string
    {
        $config    = $this->getConfig();
        $amount    = (float) $amount;
        $negative  = $amount < 0;
        $formatted = number_format(abs($amount), $decimals, $config['decimal'], $config['thousand']);

        $value = $config['position'] === 'before'
            ? $config['symbol'] . $formatted
            : $formatted . ' ' . $config['symbol'];

        return $negative ? '-' . $value : $value;
    }

    public function symbol(): string
    {
        return $this->getConfig()['symbol'];
    }

    public function code(): string
    {
        return $this->getConfig()['code'];
    }

    public function invalidateCache(): void
    {
        $this->config = null;
    }
}
