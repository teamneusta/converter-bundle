<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Converter;

use Neusta\ConverterBundle\Factory\TargetTypeFactory;
use Neusta\ConverterBundle\Populator\Populator;

/**
 * @template TSource of object
 * @template TTarget of object
 * @template TContext of object|null
 *
 * @implements Converter<TSource, TTarget, TContext>
 */
class DefaultConverter implements Converter
{
    /**
     * @param TargetTypeFactory<TTarget, TContext> $factory
     * @param array<Populator<TSource, TTarget, TContext>> $populators
     */
    public function __construct(
        private TargetTypeFactory $factory,
        private array $populators,
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
        $target = $this->factory->create($ctx);

        foreach ($this->populators as $populator) {
            $populator->populate($target, $source, $ctx);
        }

        return $target;
    }
}
