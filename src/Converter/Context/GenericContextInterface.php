<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Converter\Context;

interface GenericContextInterface
{
    public function hasKey(string $key): bool;

    public function getValue(string $key): mixed;

    public function setValue(string $key, mixed $value): static;
}
