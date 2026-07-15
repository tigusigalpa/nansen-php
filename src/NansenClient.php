<?php

declare(strict_types=1);

namespace Tigusigalpa\Nansen;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Tigusigalpa\Nansen\Endpoints\HistoricalData;
use Tigusigalpa\Nansen\Endpoints\Portfolio;
use Tigusigalpa\Nansen\Endpoints\Profiler;
use Tigusigalpa\Nansen\Endpoints\Search;
use Tigusigalpa\Nansen\Endpoints\SmartMoney;
use Tigusigalpa\Nansen\Endpoints\TokenGodMode;
use Tigusigalpa\Nansen\Http\Client as HttpClient;

final class NansenClient
{
    private readonly HttpClient $http;

    public function __construct(
        public readonly Config $config,
        ?ClientInterface $httpClient = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
    ) {
        $this->http = new HttpClient($config, $httpClient, $requestFactory, $streamFactory);
    }

    /**
     * @param array<string, mixed>|Config $config
     */
    public static function create(Config|array $config, ?ClientInterface $httpClient = null): self
    {
        if (!$config instanceof Config) {
            $config = Config::fromArray($config);
        }

        return new self($config, $httpClient);
    }

    public function smartMoney(): SmartMoney
    {
        return new SmartMoney($this->http);
    }

    public function tokenGodMode(): TokenGodMode
    {
        return new TokenGodMode($this->http);
    }

    public function profiler(): Profiler
    {
        return new Profiler($this->http);
    }

    public function portfolio(): Portfolio
    {
        return new Portfolio($this->http);
    }

    public function search(): Search
    {
        return new Search($this->http);
    }

    public function historicalData(): HistoricalData
    {
        return new HistoricalData($this->http);
    }
}
