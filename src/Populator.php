<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle;

use Neusta\ConverterBundle\Converter\Context\GenericContext;

/**
 * @template TSource of object
 * @template TTarget of object
 * @template TContext of GenericContext|null
 */
interface Populator
{
    /**
     * @param TTarget $target
     * @param TSource $source
     * @param TContext $ctx
     */
    public function populate(object $target, object $source, ?GenericContext $ctx = null): void;
}
