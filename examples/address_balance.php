<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Tigusigalpa\Nansen\NansenClient;

$client = NansenClient::create([
    'api_key' => getenv('NANSEN_API_KEY') ?: '',
]);

$address = '0x742d35Cc6634C0532925a3b8D4e6D3b6e8D3e8B1';

$balance = $client
    ->profiler()
    ->addressBalance($address)
    ->get();

foreach ($balance->items as $token) {
    printf(
        "Token: %s | Balance: %s | USD Value: %s\n",
        $token->token_symbol ?? $token->contract_address ?? 'unknown',
        $token->balance ?? 'n/a',
        $token->value_usd ?? 'n/a',
    );
}

echo "Raw response:\n";
var_dump($balance->raw);
