<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Converter\Strategy;

use Neusta\ConverterBundle\Context;

/**
 * @template TSource of object
 * @template TContext of object|null
 */
interface ConverterSelector
{
    /**
     * @param TSource  $source
     * @param Context|TContext $ctx
     */
    public function selectConverter(object $source, ?object $ctx = null): string;
}
