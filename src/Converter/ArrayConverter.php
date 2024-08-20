<?php

namespace Neusta\ConverterBundle\Converter;

use Neusta\ConverterBundle\Converter;
use Neusta\ConverterBundle\Exception\ConverterException;

class ArrayConverter
{
    public function __construct(private readonly Converter $converter)
    {
    }

    /**
     * @param array<mixed> $source
     *
     * @throws ConverterException
     */
    public function convert(array $source, ?object $ctx = null): object
    {
        $arrayObject = new \ArrayObject($source);

        return $this->converter->convert($arrayObject, $ctx);
    }
}
