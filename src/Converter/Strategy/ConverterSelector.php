<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Converter\Strategy;

use Neusta\ConverterBundle\Converter;
use Neusta\ConverterBundle\Exception\ConverterException;

/**
 * @template TSource of object
 * @template TContext of object|null
 */
interface ConverterSelector
{
    /**
     * @param TSource $source
     * @param TContext $ctx
     * @throws ConverterException
     */
    public function selectConverter(object $source, ?object $ctx = null): Converter;
}
