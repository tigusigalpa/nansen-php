<?php

declare(strict_types=1);

namespace Tigusigalpa\Nansen\Exceptions;

use Psr\Http\Message\ResponseInterface;

class ApiException extends NansenException
{
    public function __construct(
        string $message = 'Nansen API error',
        int $code = 0,
        ?\Throwable $previous = null,
        ?ResponseInterface $response = null,
    ) {
        parent::__construct($message, $code, $previous, $response);
    }
}
