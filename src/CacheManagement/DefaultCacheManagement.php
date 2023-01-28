<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\CacheManagement;

/**
 * @template TSource of object
 * @template TTarget of object
 *
 * @implements CacheManagement<TSource, TTarget>
 */
class DefaultCacheManagement implements CacheManagement
{
    /**
     * @var array<TTarget>
     */
    private array $targets = [];

    /**
     * @param CacheKeyFactory<TSource> $keyFactory
     */
    public function __construct(
        private CacheKeyFactory $keyFactory,
    ) {
    }

    public function isInCache(object $cacheKey): bool
    {
        return isset($this->targets[$this->keyFactory->createCacheKey($cacheKey)]);
    }

    public function get(object $cacheKey): object
    {
        return $this->targets[$this->keyFactory->createCacheKey($cacheKey)];
    }

    public function writeInCache(object $cacheEntry, object $cacheKey): void
    {
        $this->targets[$this->keyFactory->createCacheKey($cacheKey)] = $cacheEntry;
    }
}
