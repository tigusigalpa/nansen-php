<?php

declare(strict_types=1);

namespace Tigusigalpa\Nansen\Exceptions;

use Exception;
use Psr\Http\Message\ResponseInterface;

class NansenException extends Exception
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        protected readonly ?ResponseInterface $response = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }
}
