<?php

namespace Neusta\ConverterBundle\Converter;

interface ConverterContext
{
    public function hasKey(string $key): bool;

    public function getValue(string $key): mixed;

    public function setValue(string $key, mixed $value): DefaultConverterContext;
}