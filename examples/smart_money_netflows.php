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
    ->orderBy('net_flow_24h_usd', 'desc')
    ->page(1, 10)
    ->get();

foreach ($netflows->items as $entry) {
    printf(
        "Chain: %s | 24h Netflow: %s\n",
        $entry->chain ?? 'n/a',
        $entry->net_flow_24h_usd ?? 'n/a',
    );
}

echo "Raw payload:\n";
var_dump($netflows->raw);
