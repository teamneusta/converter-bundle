<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Converter;

use Neusta\ConverterBundle\Context;
use Neusta\ConverterBundle\Converter;
use Neusta\ConverterBundle\Converter\Context\GenericContext;
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
     * @param ConverterSelector<TSource, TContext>                 $selector
     */
    public function __construct(
        private array $converters,
        private ConverterSelector $selector,
    ) {
    }

    public function convert(object $source, ?object $ctx = null): object
    {
        if ($ctx instanceof GenericContext) {
            trigger_deprecation(
                'teamneusta/converter-bundle',
                '1.10.0',
                'Passing a "%s" is deprecated, pass a "%s" instead.',
                GenericContext::class,
                Context::class,
            );
        }

        $selectedConverterKey = $this->selector->selectConverter($source, $ctx);

        if (\array_key_exists($selectedConverterKey, $this->converters)) {
            return $this->converters[$selectedConverterKey]->convert($source, $ctx);
        }

        throw new ConverterException(\sprintf('No converter found for key <%s>', $selectedConverterKey));
    }
}
