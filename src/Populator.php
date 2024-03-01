<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle;

/**
 * @template TSource of object
 * @template TTarget of object
 * @template TContext of object|null
 */
interface Populator
{
    /**
     * @param TTarget  $target
     * @param TSource  $source
     * @param TContext $ctx
     */
    public function populate(object $target, object $source, ?object $ctx = null): void;
}
