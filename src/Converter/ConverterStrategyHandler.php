<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Converter;

use Neusta\ConverterBundle\Exception\ConverterException;
use Neusta\ConverterBundle\Strategy\ConverterSelector;

/**
 * @template S of object
 * @template T of object
 * @template C of object
 * @implements Converter<S, T, C>
 */
class ConverterStrategyHandler implements Converter
{
    /**
     * @param array<string, Converter<S, T, C>> $converters
     * @param ConverterSelector<S, C> $selector
     */
    public function __construct(
        private array $converters,
        private ConverterSelector $selector
    ) {
    }

    /**
     * @param S $source
     * @param C|null $ctx
     *
     * @return T
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
