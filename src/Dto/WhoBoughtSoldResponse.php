<?php

declare(strict_types=1);

namespace Tigusigalpa\Nansen\Dto;

final class WhoBoughtSoldResponse extends ListResponse
{
    protected function itemClass(): string
    {
        return Record::class;
    }
}
