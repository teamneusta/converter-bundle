<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Converter\Strategy;

use Neusta\ConverterBundle\Converter\Context\GenericContext;

/**
 * @template TSource of object
 * @template TContext of GenericContext|null
 */
interface ConverterSelector
{
    /**
     * @param TSource $source
     * @param TContext $ctx
     */
    public function selectConverter(object $source, ?GenericContext $ctx = null): string;
}
