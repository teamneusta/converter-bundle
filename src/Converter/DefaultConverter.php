<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Converter;

use Neusta\ConverterBundle\Factory\TargetTypeFactory;
use Neusta\ConverterBundle\Populator\Populator;

/**
 * @template S of object
 * @template T of object
 * @template C of object
 * @implements Converter<S, T, C>
 */
class DefaultConverter implements Converter
{
    /**
     * @param TargetTypeFactory<T, C> $factory
     * @param array<Populator<S, T, C>> $populators
     */
    public function __construct(
        private TargetTypeFactory $factory,
        private array $populators,
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
        $target = $this->factory->create($ctx);

        foreach ($this->populators as $populator) {
            $populator->populate($target, $source, $ctx);
        }

        return $target;
    }
}
