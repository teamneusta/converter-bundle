<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Converter;

use Neusta\ConverterBundle\Converter;
use Neusta\ConverterBundle\Exception\ConverterException;
use Neusta\ConverterBundle\Converter\Strategy\ConverterSelector;

/**
 * @template TSource of object
 * @template TTarget of object
 * @template TContext of object|null
 *
 * @implements Converter<TSource, TTarget, TContext>
 */
final class StrategicConverter implements Converter
{
    /**
     * @param array<string, Converter<TSource, TTarget, TContext>> $converters
     * @param ConverterSelector<TSource, TContext> $selector
     */
    public function __construct(
        private ConverterSelector $selector
    ) {
    }

    /**
     * @param TSource $source
     * @param TContext $ctx
     *
     * @return TTarget
     */
    public function convert(object $source, ?object $ctx = null): object
    {
        return $this->selector->selectConverter($source, $ctx)->convert($source, $ctx);
    }
}
