<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\CacheManagement;

/**
 * @template TSource of object
 * @template TTarget of object
 */
interface CacheManagement
{
    /**
     * @param TSource $cacheKey
     */
    public function isInCache(object $cacheKey): bool;

    /**
     * @param TSource $cacheKey
     *
     * @return TTarget
     */
    public function get(object $cacheKey): object;

    /**
     * @param TTarget $cacheEntry
     * @param TSource $cacheKey
     */
    public function writeInCache(object $cacheEntry, object $cacheKey): void;
}
