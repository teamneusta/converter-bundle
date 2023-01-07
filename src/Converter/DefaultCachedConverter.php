<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Converter;

use Neusta\ConverterBundle\CacheManagement\CacheManagement;
use Neusta\ConverterBundle\Factory\TargetTypeFactory;
use Neusta\ConverterBundle\Populator\Populator;

/**
 * @template S of object
 * @template T of object
 * @implements CachedConverter<S, T>
 */
class DefaultCachedConverter implements CachedConverter
{
    /**
     * @param TargetTypeFactory<T> $factory
     * @param array<Populator<S, T>> $populators
     * @param CacheManagement<S, T> $cacheManagement
     */
    public function __construct(
        private TargetTypeFactory $factory,
        private array $populators,
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

        $target = $this->factory->create($ctx);

        foreach ($this->populators as $populator) {
            $populator->populate($target, $source, $ctx);
        }

        $this->cacheManagement->writeInCache($target, $source);

        return $target;
    }
}
