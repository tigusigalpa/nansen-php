# Nansen PHP/Laravel Client/SDK/Library

![Nansen AI PHP SDK](https://i.postimg.cc/nzPpynS6/nansen-ai-php-banner.jpg)

[![CI](https://github.com/tigusigalpa/nansen-php/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/tigusigalpa/nansen-php/actions/workflows/ci.yml)
[![Tests](https://github.com/tigusigalpa/nansen-php/actions/workflows/test.yml/badge.svg?branch=main)](https://github.com/tigusigalpa/nansen-php/actions/workflows/test.yml)
[![CodeQL](https://github.com/tigusigalpa/nansen-php/actions/workflows/codeql.yml/badge.svg?branch=main)](https://github.com/tigusigalpa/nansen-php/actions/workflows/codeql.yml)
[![Codecov](https://codecov.io/gh/tigusigalpa/nansen-php/graph/badge.svg)](https://codecov.io/gh/tigusigalpa/nansen-php)
[![PHP Version](https://img.shields.io/badge/php-%5E8.1-8892BF.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Latest Stable Version](https://img.shields.io/packagist/v/tigusigalpa/nansen-php.svg)](https://packagist.org/packages/tigusigalpa/nansen-php)
[![GitHub Release](https://img.shields.io/github/v/release/tigusigalpa/nansen-php?style=flat-square)](https://github.com/tigusigalpa/nansen-php/releases)

> A PHP client for the [Nansen AI API](https://docs.nansen.ai/). Works in any PHP 8.1+ project, and comes with proper
> Laravel 10–13 support out of the box.

I built this because talking to the Nansen API by hand gets old fast — you end up copy-pasting the same cURL
boilerplate, decoding JSON, and reinventing retry logic every time. This library wraps all of that behind a fluent
interface, so a request reads more or less like a sentence.

```php
use Tigusigalpa\Nansen\NansenClient;

$client = NansenClient::create(['api_key' => 'YOUR_API_KEY']);

$netflows = $client
    ->smartMoney()
    ->netflows()
    ->chains(['ethereum'])
    ->page(1, 10)
    ->get();

foreach ($netflows->items as $entry) {
    echo $entry->chain . ' → ' . $entry->net_flow_24h_usd . "\n";
}
```

---

## What you get

- **No framework required.** The core is plain PHP 8.1+, so you can drop it into anything.
- **Laravel, if you want it.** Auto-discovered service provider, a publishable config file, and a `Nansen` facade so you
  can write `Nansen::smartMoney()->netflows()`.
- **Bring your own HTTP client.** Guzzle is used by default, but anything PSR-18 works — just inject it.
- **A fluent API that actually reads well:** `->smartMoney()->netflows()->chains(['ethereum'])->page(1, 10)->get()`.
- **Typed responses, not loose arrays.** Everything comes back as a DTO, and lists are real collections you can
  `count()`, loop over, and index into.
- **You never lose data.** Each DTO keeps the untouched API response in `->raw`, so if Nansen adds a field tomorrow, you
  can still read it today.
- **Rate limits handled for you.** When the API returns a 429, the client backs off and retries automatically, honoring
  `Retry-After` and reset headers; transient 5xx and transport failures are retried too. There's a clear exception hierarchy (`ApiException`, `RateLimitException`, `UnauthorizedException`,
  `NotFoundException`) for everything else.

---

## Installation

```bash
composer require tigusigalpa/nansen-php
```

That's it for plain PHP. If you're on Laravel, read on.

### Laravel

The service provider is auto-discovered, so there's nothing to register manually. Publish the config file when you want
to tweak defaults:

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
    'max_retry_delay' => 30,
];
```

Want to use your own HTTP client (say, one with custom middleware or logging)? Bind a PSR-18 implementation in a
service provider; Nansen will use it unless `nansen.http_client` explicitly names a different client:

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
    ->with('timeframe', '24h')
    ->filters([
        'market_cap_usd' => ['min' => 1_000_000],
    ])
    ->page(1, 25)
    ->get();

foreach ($screener->items as $signal) {
    echo $signal->token_symbol . ': ' . $signal->volume . "\n";
}
```

### Laravel Facade

```php
use Tigusigalpa\Nansen\Laravel\Facades\Nansen;

$balances = Nansen::profiler()
    ->addressCurrentBalance('0x1234...', 'ethereum')
    ->get();

$netflows = Nansen::smartMoney()
    ->netflows()
    ->chains(['ethereum', 'arbitrum'])
    ->orderBy('net_flow_24h_usd', 'desc')
    ->page(1, 50)
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

Here's what's covered so far. If Nansen ships something new, the `raw` payload means you're not blocked while waiting
for an update.

| Category            | Endpoint                                                                                                                  |
|---------------------|---------------------------------------------------------------------------------------------------------------------------|
| **Smart Money**     | `smartMoney()->netflows()` · `smartMoney()->holdings()` · `smartMoney()->dexTrades()`                                     |
| **Token God Mode**  | `tokenGodMode()->tokenScreener()` · `tokenGodMode()->flowIntelligence()` · `tokenGodMode()->whoBoughtSold()`              |
| **Profiler**        | `profiler()->addressCurrentBalance($address, $chain)` · `profiler()->addressDexTrades($address, $chain, $date)` · `profiler()->addressLabels($address, $chain)` |
| **Portfolio**       | `portfolio()->defiHoldings($walletAddress)`                                                                                |
| **Search**          | `search()->entityName($searchQuery)`                                                                                       |
| **Historical Data** | `historicalData()->tokenFlowSummary($chain, $tokenAddress, $dateRange)` · `historicalData()->smartMoneyTokenBalances($asOfDate)` |

Use `with()` or `withAll()` for the top-level parameters required by a specific endpoint, such as `chain`,
`token_address`, `date`, or `timeframe`. Use `filters()` only for the API's nested `filters` object; calls are merged,
so a query can be built in pieces without clobbering earlier filters. For modern v1 pagination, use `page()` and
`perPage()`; `limit()` remains as a compatibility alias for `per_page`.

---

## Working with responses

Every response is a typed DTO. You can loop over the typed items, and when you need something the library doesn't map
yet, reach straight into `->raw`:

```php
$netflows = $client->smartMoney()->netflows()->page(1, 5)->get();

// Typed items
foreach ($netflows->items as $item) {
    echo $item->chain;
}

// Future-proof access
$brandNewField = $netflows->raw['data'][0]['brand_new_field'] ?? null;
```

Portfolio responses expose their native shape rather than an artificial list: use `$portfolio->summary` and
`$portfolio->protocols`. Paginated responses expose v1 metadata through `->meta`, including `page`, `perPage`, and
`isLastPage`.

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

Built by [Igor Sazonov](mailto:sovletig@gmail.com). Found a bug or missing an endpoint? Open an issue or a PR,
contributions are welcome.
