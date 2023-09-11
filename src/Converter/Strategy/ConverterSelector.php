<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Converter\Strategy;

/**
 * @template TSource of object
 * @template TContext of object|null
 */
interface ConverterSelector
{
    /**
     * @param TSource  $source
     * @param TContext $ctx
     */
    public function selectConverter(object $source, object $ctx = null): string;
}
