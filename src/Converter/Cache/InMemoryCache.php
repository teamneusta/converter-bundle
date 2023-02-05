<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Converter\Cache;

use Neusta\ConverterBundle\Converter\Cache;

/**
 * @template TSource of object
 * @template TTarget of object
 * @template TContext of CacheAwareContext|null
 *
 * @implements Cache<TSource, TTarget, TContext>
 */
final class InMemoryCache implements Cache
{
    /**
     * @var array<string, TTarget>
     */
    private array $targets = [];

    /**
     * @param CacheKeyFactory<TSource> $keyFactory
     */
    public function __construct(
        private CacheKeyFactory $keyFactory,
    ) {
    }

    public function get(object $source, ?CacheAwareContext $ctx = null): ?object
    {
        return $this->targets[$this->createCacheKeyFor($source, $ctx)] ?? null;
    }

    public function set(object $source, object $target, ?CacheAwareContext $ctx = null): void
    {
        $this->targets[$this->createCacheKeyFor($source, $ctx)] = $target;
    }

    /**
     * @param TSource $source
     */
    private function createCacheKeyFor(object $source, ?CacheAwareContext $ctx = null): string
    {
        return $this->keyFactory->createCacheKeyFor($source) . $ctx?->getHash();
    }
}
