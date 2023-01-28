<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Converter\Cache;

/**
 * @template TSource of object
 * @template TTarget of object
 *
 * @implements Cache<TSource, TTarget>
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

    public function get(object $source): ?object
    {
        return $this->targets[$this->keyFactory->createFor($source)] ?? null;
    }

    public function set(object $source, object $target): void
    {
        $this->targets[$this->keyFactory->createFor($source)] = $target;
    }
}
