<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Tigusigalpa\Nansen\NansenClient;

$client = NansenClient::create([
    'api_key' => getenv('NANSEN_API_KEY') ?: '',
]);

$screener = $client
    ->tokenGodMode()
    ->tokenScreener()
    ->chains(['ethereum', 'arbitrum'])
    ->with('timeframe', '24h')
    ->filters([
        'market_cap_usd' => [
            'min' => 1_000_000,
            'max' => 500_000_000,
        ],
    ])
    ->orderBy('market_cap_usd', 'desc')
    ->page(1, 25)
    ->get();

foreach ($screener->items as $signal) {
    printf(
        "Token: %s | Signal: %s | Market Cap: %s\n",
        $signal->token_symbol ?? 'n/a',
        $signal->signal_name ?? 'n/a',
        $signal->market_cap_usd ?? 'n/a',
    );
}

echo "Total results: " . count($screener->items) . "\n";

// The entire raw payload is always preserved for forward compatibility.
var_dump($screener->raw);
