<?php

namespace Neusta\ConverterBundle\Tests\Selector;

use Neusta\ConverterBundle\Converter;
use Neusta\ConverterBundle\Converter\Strategy\ConverterSelector;
use Neusta\ConverterBundle\Exception\ConverterException;

/**
 *
 */
class DefaultConverterSelector implements ConverterSelector
{
    /**
     * @param array<string, Converter> $converters
     */
    public function __construct(
        private array $converters,
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function selectConverter(object $source, ?object $ctx = null): Converter
    {
        if ($ctx->hasKey('converterKey')) {
            return $this->converters[$ctx->getKey('converterKey')]->convert($source, $ctx);
        }
        throw new ConverterException(sprintf("No converter found for key <%s>", $selectedConverterKey));
    }
}
