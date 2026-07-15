<?php

declare(strict_types=1);

namespace Tigusigalpa\Nansen\Exceptions;

use Psr\Http\Message\ResponseInterface;

class UnauthorizedException extends ApiException
{
    public function __construct(
        string $message = 'Unauthorized. Please check your Nansen API key.',
        int $code = 401,
        ?\Throwable $previous = null,
        ?ResponseInterface $response = null,
    ) {
        parent::__construct($message, $code, $previous, $response);
    }
}
