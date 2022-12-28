<?php

namespace Neusta\ConverterBundle\CacheManagement;

/**
 * @template S of object
 * @template T of object
 */
interface CacheManagement
{
    /**
     * @param S $cacheKey
     */
    public function isInCache(object $cacheKey): bool;

    /**
     * @param S $cacheKey
     * @return T
     */
    public function get(object $cacheKey): object;

    /**
     * @param T $cacheEntry
     * @param S $cacheKey
     */
    public function writeInCache(object $cacheEntry, object $cacheKey): void;
}
