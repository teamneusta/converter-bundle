<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Converter;

use Neusta\ConverterBundle\Converter;
use Neusta\ConverterBundle\Converter\Strategy\ConverterSelector;
use Neusta\ConverterBundle\Exception\ConverterException;

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
        private array $converters,
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
        $selectedConverterKey = $this->selector->selectConverter($source, $ctx);
        if (array_key_exists($selectedConverterKey, $this->converters)) {
            return $this->converters[$selectedConverterKey]->convert($source, $ctx);
        }
        throw new ConverterException(sprintf("No converter found for key <%s>", $selectedConverterKey));
    }
}
