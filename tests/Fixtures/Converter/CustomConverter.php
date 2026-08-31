<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Fixtures\Converter;

use Neusta\ConverterBundle\Context;
use Neusta\ConverterBundle\Converter;
use Neusta\ConverterBundle\Populator;
use Neusta\ConverterBundle\TargetFactory;

/**
 * A custom converter as the deprecated `neusta_converter.converter.<id>.converter` option allows it:
 * it must accept exactly the same constructor arguments as the `GenericConverter`.
 *
 * @template TSource of object
 * @template TTarget of object
 * @template TContext of object|null = null
 *
 * @implements Converter<TSource, TTarget, TContext>
 */
final class CustomConverter implements Converter
{
    /**
     * @param TargetFactory<TTarget, TContext>             $factory
     * @param array<Populator<TSource, TTarget, TContext>> $populators
     */
    public function __construct(
        private TargetFactory $factory,
        private array $populators,
    ) {
    }

    /**
     * @param TSource          $source
     * @param Context|TContext $ctx
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
