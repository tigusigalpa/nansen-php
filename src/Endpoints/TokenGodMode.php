<?php

declare(strict_types=1);

namespace Tigusigalpa\Nansen\Endpoints;

use Tigusigalpa\Nansen\Dto\FlowIntelligenceResponse;
use Tigusigalpa\Nansen\Dto\TokenScreenerResponse;
use Tigusigalpa\Nansen\Dto\WhoBoughtSoldResponse;
use Tigusigalpa\Nansen\Http\Client as HttpClient;
use Tigusigalpa\Nansen\RequestBuilder;

final class TokenGodMode
{
    public function __construct(private readonly HttpClient $http)
    {
    }

    public function tokenScreener(): RequestBuilder
    {
        return new RequestBuilder($this->http, 'POST', 'api/v1/token-screener', TokenScreenerResponse::class);
    }

    public function flowIntelligence(): RequestBuilder
    {
        return new RequestBuilder($this->http, 'POST', 'api/v1/tgm/flow-intelligence', FlowIntelligenceResponse::class);
    }

    public function whoBoughtSold(): RequestBuilder
    {
        return new RequestBuilder($this->http, 'POST', 'api/v1/tgm/who-bought-sold', WhoBoughtSoldResponse::class);
    }
}
