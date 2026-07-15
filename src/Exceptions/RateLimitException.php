<?php

declare(strict_types=1);

namespace Tigusigalpa\Nansen\Exceptions;

use Psr\Http\Message\ResponseInterface;

class RateLimitException extends ApiException
{
    public function __construct(
        string $message = 'Rate limit exceeded',
        int $code = 429,
        ?\Throwable $previous = null,
        ?ResponseInterface $response = null,
        protected readonly int $retryAfter = 0,
        protected readonly int $remaining = 0,
    ) {
        parent::__construct($message, $code, $previous, $response);
    }

    public function retryAfter(): int
    {
        return $this->retryAfter;
    }

    public function remaining(): int
    {
        return $this->remaining;
    }
}
