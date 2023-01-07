<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Converter;

/**
 * @template S of object
 * @template T of object
 */
interface Converter
{
    /**
     * @param S $source
     *
     * @return T target type of your conversion
     */
    public function convert(object $source, ?ConverterContext $ctx = null): object;
}
