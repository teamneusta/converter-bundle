<?php

namespace Neusta\ConverterBundle\CacheManagement;

/**
 * @template S of object
 * @template T of object
 * @implements CacheManagement<S, T>
 */
class DefaultCacheManagement implements CacheManagement
{
    /**
     * @var array<T>
     */
    private array $targets = [];

    /**
     * @param CacheKeyFactory<T> $keyFactory
     */
    public function __construct(
        private CacheKeyFactory $keyFactory,
    ) {
    }

    public function isInCache(object $cacheKey): bool
    {
        return array_key_exists($this->keyFactory->createCacheKey($cacheKey), $this->targets);
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
