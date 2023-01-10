<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Converter;

use Neusta\ConverterBundle\Exception\ConverterException;

/**
 * @template S of object
 * @template T of object
 * @template C of object
 */
interface Converter
{
    /**
     * @param S $source
     * @param C|null $ctx
     *
     * @return T target type of your conversion
     * @throws ConverterException
     */
    public function convert(object $source, ?object $ctx = null): object;
}
