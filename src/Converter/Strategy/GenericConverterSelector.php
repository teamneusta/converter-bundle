<?php

namespace Neusta\ConverterBundle\Converter\Strategy;

use Neusta\ConverterBundle\Converter;
use Neusta\ConverterBundle\Converter\Context\GenericContext;
use Neusta\ConverterBundle\Exception\ConverterException;

/**
 * @template TSource of object
 * @implements ConverterSelector<TSource, GenericContext>
 */
class GenericConverterSelector implements ConverterSelector
{
    /**
     * @param array<Converter> $converters
     * @param string $ctxKey
     */
    public function __construct(
        private array $converters,
        private string $ctxKey,
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function selectConverter(object $source, ?object $ctx = null): Converter
    {
        $selectedConverterKey = '';
        if ($ctx->hasKey($this->ctxKey)) {
            $selectedConverterKey = $ctx->getValue($this->ctxKey);
        }

        if (array_key_exists($selectedConverterKey,$this->converters)) {
            return $this->converters[$selectedConverterKey];
        }
        throw new ConverterException(sprintf("No converter found for key <%s>", $selectedConverterKey));
    }
}
