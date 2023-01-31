<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Converter;

use Neusta\ConverterBundle\CacheManagement\CacheManagement;
use Neusta\ConverterBundle\Converter;

/**
 * @template TSource of object
 * @template TTarget of object
 * @template TContext of object|null
 *
 * @implements Converter<TSource, TTarget, TContext>
 */
class CachedConverter implements Converter
{
    /**
     * @param Converter<TSource, TTarget, TContext> $inner
     * @param CacheManagement<TSource, TTarget> $cacheManagement
     */
    public function __construct(
        private Converter $inner,
        private CacheManagement $cacheManagement,
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
        if ($this->cacheManagement->isInCache($source)) {
            return $this->cacheManagement->get($source);
        }

        $target = $this->inner->convert($source, $ctx);

        $this->cacheManagement->writeInCache($target, $source);

        return $target;
    }
}
