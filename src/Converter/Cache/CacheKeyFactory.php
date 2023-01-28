<?php

declare(strict_types=1);


namespace Neusta\ConverterBundle\Converter\Cache;

/**
 * @template TSource of object
 * @template TContext of object|null
 */
interface CacheKeyFactory
{
    /**
     * @param TSource $source
     * @param TContext $ctx
     *
     * @return non-empty-string
     */
    public function createFor(object $source, ?object $ctx = null): string;
}
