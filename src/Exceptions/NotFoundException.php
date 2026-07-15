<?php

declare(strict_types=1);

namespace Tigusigalpa\Nansen\Exceptions;

use Psr\Http\Message\ResponseInterface;

class NotFoundException extends ApiException
{
    public function __construct(
        string $message = 'Requested resource was not found on the Nansen API.',
        int $code = 404,
        ?\Throwable $previous = null,
        ?ResponseInterface $response = null,
    ) {
        parent::__construct($message, $code, $previous, $response);
    }
}
