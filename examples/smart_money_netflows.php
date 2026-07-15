<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Tigusigalpa\Nansen\NansenClient;

$client = NansenClient::create([
    'api_key' => getenv('NANSEN_API_KEY') ?: '',
]);

$netflows = $client
    ->smartMoney()
    ->netflows()
    ->chains(['ethereum'])
    ->filters([
        'timeframe' => '24h',
    ])
    ->orderBy('timestamp', 'desc')
    ->limit(10)
    ->get();

foreach ($netflows->items as $entry) {
    printf(
        "Chain: %s | Netflow: %s | Timestamp: %s\n",
        $entry->chain ?? 'n/a',
        $entry->netflow ?? $entry->netflow_usd ?? 'n/a',
        $entry->timestamp ?? 'n/a',
    );
}

echo "Raw payload:\n";
var_dump($netflows->raw);
