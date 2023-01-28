<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Converter\Cache;

/**
 * @template TSource of object
 * @template TTarget of object
 * @template TContext of object|null
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
     * @param CacheKeyFactory<TSource, TContext> $keyFactory
     */
    public function __construct(
        private CacheKeyFactory $keyFactory,
    ) {
    }

    public function get(object $source, ?object $ctx = null): ?object
    {
        return $this->targets[$this->keyFactory->createFor($source, $ctx)] ?? null;
    }

    public function set(object $source, object $target, ?object $ctx = null): void
    {
        $this->targets[$this->keyFactory->createFor($source, $ctx)] = $target;
    }
}
