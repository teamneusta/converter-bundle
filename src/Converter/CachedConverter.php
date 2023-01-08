<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Converter;

use Neusta\ConverterBundle\CacheManagement\CacheManagement;

/**
 * @template S of object
 * @template T of object
 * @template C of object
 * @implements Converter<S, T, C>
 */
class CachedConverter implements Converter
{
    /**
     * @param Converter<S, T, C> $inner
     * @param CacheManagement<S, T> $cacheManagement
     */
    public function __construct(
        private Converter $inner,
        private CacheManagement $cacheManagement,
    ) {
    }

    /**
     * @param S $source
     * @param C|null $ctx
     *
     * @return T
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
