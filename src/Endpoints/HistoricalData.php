<?php

declare(strict_types=1);

namespace Tigusigalpa\Nansen\Endpoints;

use InvalidArgumentException;
use Tigusigalpa\Nansen\Dto\HistoricalDataResponse;
use Tigusigalpa\Nansen\Http\Client as HttpClient;
use Tigusigalpa\Nansen\RequestBuilder;

final class HistoricalData
{
    public function __construct(private readonly HttpClient $http)
    {
    }

    public function query(string $path): RequestBuilder
    {
        if ($path === '') {
            throw new InvalidArgumentException('Historical data path cannot be empty.');
        }

        $uri = 'api/v1beta1/' . ltrim($path, '/');

        return new RequestBuilder($this->http, 'POST', $uri, HistoricalDataResponse::class);
    }

    public function netflows(): RequestBuilder
    {
        return $this->query('smart-money/netflow');
    }

    public function holdings(): RequestBuilder
    {
        return $this->query('smart-money/holdings');
    }

    public function dexTrades(): RequestBuilder
    {
        return $this->query('smart-money/dex-trades');
    }
}
