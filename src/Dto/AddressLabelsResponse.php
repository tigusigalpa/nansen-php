<?php

declare(strict_types=1);

namespace Tigusigalpa\Nansen\Dto;

final class AddressLabelsResponse extends ListResponse
{
    protected function itemClass(): string
    {
        return Record::class;
    }
}
