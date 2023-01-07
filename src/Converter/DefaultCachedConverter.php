<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Converter;

use Neusta\ConverterBundle\CacheManagement\CacheManagement;

/**
 * @template S of object
 * @template T of object
 * @implements CachedConverter<S, T>
 */
class DefaultCachedConverter implements CachedConverter
{
    /**
     * @param Converter<S, T> $inner
     * @param CacheManagement<S, T> $cacheManagement
     */
    public function __construct(
        private Converter $inner,
        private CacheManagement $cacheManagement,
    ) {
    }

    /**
     * @param S $source
     *
     * @return T
     */
    public function convert(object $source, ?ConverterContext $ctx = null): object
    {
        if ($this->cacheManagement->isInCache($source)) {
            return $this->cacheManagement->get($source);
        }

        $target = $this->inner->convert($source, $ctx);

        $this->cacheManagement->writeInCache($target, $source);

        return $target;
    }
}
