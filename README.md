# Nansen PHP

[![PHP Version](https://img.shields.io/badge/php-%5E8.1-8892BF.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Latest Stable Version](https://img.shields.io/packagist/v/tigusigalpa/nansen-php.svg)](https://packagist.org/packages/tigusigalpa/nansen-php)

> A PHP client for the [Nansen AI API](https://docs.nansen.ai/). Works in any PHP 8.1+ project, and comes with proper Laravel 10–13 support out of the box.

I built this because talking to the Nansen API by hand gets old fast — you end up copy-pasting the same cURL boilerplate, decoding JSON, and reinventing retry logic every time. This library wraps all of that behind a fluent interface, so a request reads more or less like a sentence.

```php
use Tigusigalpa\Nansen\NansenClient;

$client = NansenClient::create(['api_key' => 'YOUR_API_KEY']);

$netflows = $client
    ->smartMoney()
    ->netflows()
    ->chains(['ethereum'])
    ->limit(10)
    ->get();

foreach ($netflows->items as $entry) {
    echo $entry->chain . ' → ' . $entry->netflow . "\n";
}
```

---

## What you get

- **No framework required.** The core is plain PHP 8.1+, so you can drop it into anything.
- **Laravel, if you want it.** Auto-discovered service provider, a publishable config file, and a `Nansen` facade so you can write `Nansen::smartMoney()->netflows()`.
- **Bring your own HTTP client.** Guzzle is used by default, but anything PSR-18 works — just inject it.
- **A fluent API that actually reads well:** `->smartMoney()->netflows()->chains(['ethereum'])->limit(10)->get()`.
- **Typed responses, not loose arrays.** Everything comes back as a DTO, and lists are real collections you can `count()`, loop over, and index into.
- **You never lose data.** Each DTO keeps the untouched API response in `->raw`, so if Nansen adds a field tomorrow, you can still read it today.
- **Rate limits handled for you.** When the API returns a 429, the client backs off and retries automatically, honoring `Retry-After`. There's a clear exception hierarchy (`ApiException`, `RateLimitException`, `UnauthorizedException`, `NotFoundException`) for everything else.

---

## Installation

```bash
composer require tigusigalpa/nansen-php
```

That's it for plain PHP. If you're on Laravel, read on.

### Laravel

The service provider is auto-discovered, so there's nothing to register manually. Publish the config file when you want to tweak defaults:

```bash
php artisan vendor:publish --provider="Tigusigalpa\Nansen\Laravel\NansenServiceProvider"
```

Then set your environment variable:

```env
NANSEN_API_KEY=your_api_key_here
```

---

## Configuration

Everything lives in `config/nansen.php` after publishing:

```php
return [
    'api_key'     => env('NANSEN_API_KEY'),
    'base_uri'    => env('NANSEN_BASE_URI', 'https://api.nansen.ai'),
    'timeout'     => 30,
    'retries'     => 3,
    'retry_delay' => 1,
];
```

Want to use your own HTTP client (say, one with custom middleware or logging)? Bind any PSR-18 implementation in a service provider and the library will pick it up:

```php
$this->app->bind(\Psr\Http\Client\ClientInterface::class, MyPsr18Client::class);
```

---

## Quick Start

### Standalone

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Tigusigalpa\Nansen\NansenClient;

$client = NansenClient::create([
    'api_key' => getenv('NANSEN_API_KEY'),
]);

$screener = $client
    ->tokenGodMode()
    ->tokenScreener()
    ->chains(['ethereum'])
    ->filters([
        'market_cap_usd' => ['min' => 1_000_000],
    ])
    ->limit(25)
    ->get();

foreach ($screener->items as $signal) {
    echo $signal->token_symbol . ': ' . $signal->signal_name . "\n";
}
```

### Laravel Facade

```php
use Tigusigalpa\Nansen\Laravel\Facades\Nansen;

$balances = Nansen::profiler()
    ->addressBalance('0x1234...')
    ->get();

$netflows = Nansen::smartMoney()
    ->netflows()
    ->chains(['ethereum', 'arbitrum'])
    ->orderBy('timestamp', 'desc')
    ->limit(50)
    ->get();
```

### Error Handling

```php
use Tigusigalpa\Nansen\Exceptions\NotFoundException;
use Tigusigalpa\Nansen\Exceptions\RateLimitException;
use Tigusigalpa\Nansen\Exceptions\UnauthorizedException;

try {
    $result = $client->profiler()->addressBalance('0x...')->get();
} catch (NotFoundException $e) {
    // Address or resource not found
} catch (RateLimitException $e) {
    // Rate limited; $e->retryAfter() and $e->remaining() are available
} catch (UnauthorizedException $e) {
    // Invalid or expired API key
}
```

---

## Endpoints

Here's what's covered so far. If Nansen ships something new, the `raw` payload means you're not blocked while waiting for an update.

| Category            | Endpoint                                                                                   |
| ------------------- | ------------------------------------------------------------------------------------------ |
| **Smart Money**     | `smartMoney()->netflows()` · `smartMoney()->holdings()` · `smartMoney()->dexTrades()`       |
| **Token God Mode**  | `tokenGodMode()->tokenScreener()` · `tokenGodMode()->flowIntelligence()` · `tokenGodMode()->whoBoughtSold()` |
| **Profiler**        | `profiler()->addressBalance($address)` · `profiler()->addressDexTrades($address)` · `profiler()->addressLabels($address)` |
| **Portfolio**       | `portfolio()->defiHoldings($address)`                                                      |
| **Search**          | `search()->general($query)` · `search()->entity($entityId)`                                |
| **Historical Data** | `historicalData()->...` (v1beta1 backtesting endpoints)                                    |

Every endpoint shares the same set of modifiers — `chains()`, `filters()`, `orderBy()`, `limit()`, `offset()`, and `pagination()` — so once you've used one, you already know the rest. Calls to `filters()` are merged, so you can build a query up in pieces without clobbering earlier filters.

---

## Working with responses

Every response is a typed DTO. You can loop over the typed items, and when you need something the library doesn't map yet, reach straight into `->raw`:

```php
$netflows = $client->smartMoney()->netflows()->limit(5)->get();

// Typed items
foreach ($netflows->items as $item) {
    echo $item->chain;
}

// Future-proof access
$brandNewField = $netflows->raw['data'][0]['brand_new_field'] ?? null;
```

---

## Running the tests

```bash
composer install
vendor/bin/phpunit
```

The suite runs against a mocked HTTP client, so no API key or network access is needed.

---

## License

MIT. Do what you like with it — see the [LICENSE](LICENSE) file for the details.

Built by [Igor Sazonov](mailto:sovletig@gmail.com). Found a bug or missing an endpoint? Open an issue or a PR, contributions are welcome.
