<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Tigusigalpa\Nansen\NansenClient;

$apiKey = getenv('NANSEN_API_KEY');

if (!$apiKey) {
    echo "Please set the NANSEN_API_KEY environment variable.\n";
    exit(1);
}

$client = NansenClient::create([
    'api_key' => $apiKey,
    'base_uri' => 'https://api.nansen.ai',
    'timeout' => 30,
    'retries' => 3,
    'retry_delay' => 1,
]);

echo "Nansen client initialized successfully.\n";
