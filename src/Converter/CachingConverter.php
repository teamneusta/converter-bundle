<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Converter;

use Neusta\ConverterBundle\Converter\Cache\CacheAwareContext;
use Neusta\ConverterBundle\Converter;

/**
 * @template TSource of object
 * @template TTarget of object
 * @template TContext of CacheAwareContext|null
 *
 * @implements Converter<TSource, TTarget, TContext>
 */
final class CachingConverter implements Converter
{
    /**
     * @param Converter<TSource, TTarget, TContext> $inner
     * @param Cache<TSource, TTarget, TContext> $cache
     */
    public function __construct(
        private Converter $inner,
        private Cache $cache,
    ) {
    }

    /**
     * @param TSource $source
     * @param TContext $ctx
     *
     * @return TTarget
     */
    public function convert(object $source, ?object $ctx = null): object
    {
        if ($target = $this->cache->get($source, $ctx)) {
            return $target;
        }

        $target = $this->inner->convert($source, $ctx);

        $this->cache->set($source, $target, $ctx);

        return $target;
    }
}
