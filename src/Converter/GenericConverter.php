<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Converter;

use Neusta\ConverterBundle\Converter;
use Neusta\ConverterBundle\Populator;
use Neusta\ConverterBundle\TargetFactory;

/**
 * @template TTarget of object
 * @template TSource of object
 * @template TContext of object|null
 *
 * @implements Converter<TSource, TTarget, TContext>
 */
final class GenericConverter implements Converter
{
    /**
     * @param TargetFactory<TTarget, TContext>             $factory
     * @param array<Populator<TTarget, TSource, TContext>> $populators
     */
    public function __construct(
        private TargetFactory $factory,
        private array $populators,
    ) {
    }

    /**
     * @param TSource  $source
     * @param TContext $ctx
     *
     * @return TTarget
     */
    public function convert(object $source, object $ctx = null): object
    {
        $target = $this->factory->create($ctx);

        foreach ($this->populators as $populator) {
            $populator->populate($target, $source, $ctx);
        }

        return $target;
    }
}
