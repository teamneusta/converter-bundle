<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Converter\Cache;

/**
 * @template TSource of object
 * @template TTarget of object
 * @template TContext of object|null
 */
interface Cache
{
    /**
     * @param TSource $source
     * @param TContext $ctx
     *
     * @return TTarget|null
     */
    public function get(object $source, ?object $ctx = null): ?object;

    /**
     * @param TSource $source
     * @param TTarget $target
     * @param TContext $ctx
     */
    public function set(object $source, object $target, ?object $ctx = null): void;
}
