<?php

declare(strict_types=1);

namespace Tigusigalpa\Nansen\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use Tigusigalpa\Nansen\NansenClient;

/**
 * @mixin NansenClient
 */
final class Nansen extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return NansenClient::class;
    }
}
