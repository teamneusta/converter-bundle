<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle;

use Neusta\ConverterBundle\Converter\Context\ContextInterface;
use Neusta\ConverterBundle\Exception\ConverterException;

/**
 * @template TSource of object
 * @template TTarget of object
 * @template TContext of ContextInterface|null
 */
interface Converter
{
    /**
     * @param TSource $source
     * @param TContext $ctx
     *
     * @return TTarget target type of your conversion
     *
     * @throws ConverterException
     */
    public function convert(object $source, ?ContextInterface $ctx = null): object;
}
