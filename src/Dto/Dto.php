<?php

declare(strict_types=1);

namespace Tigusigalpa\Nansen\Dto;

abstract class Dto
{
    public function __construct(public readonly array $raw)
    {
    }
}
