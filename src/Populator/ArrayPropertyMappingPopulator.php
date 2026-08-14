<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Populator;

use Neusta\ConverterBundle\Exception\PopulationException;
use Neusta\ConverterBundle\Populator;
use Neusta\ConverterBundle\Populator\Mapper\ArrayPropertyMapper;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * @template TSource of object
 * @template TTarget of object
 * @template TContext of object|null
 *
 * @implements Populator<TSource, TTarget, TContext>
 */
final class ArrayPropertyMappingPopulator implements Populator
{
    /** @var PropertyMappingPopulator<TSource, TTarget, TContext> */
    private readonly PropertyMappingPopulator $populator;

    /**
     * @param \Closure(mixed, TContext=):mixed|null $mapper
     */
    public function __construct(
        string $targetProperty,
        string $sourceArrayProperty,
        ?string $sourceArrayItemProperty = null,
        ?\Closure $mapper = null,
        ?PropertyAccessorInterface $arrayItemAccessor = null,
        ?PropertyAccessorInterface $accessor = null,
    ) {
        /** @var PropertyMappingPopulator<TSource, TTarget, TContext> $populator */
        $populator = new PropertyMappingPopulator(
            $targetProperty,
            $sourceArrayProperty,
            null,
            new ArrayPropertyMapper(
                $sourceArrayItemProperty,
                $mapper,
                $arrayItemAccessor ?? PropertyAccess::createPropertyAccessor(),
            ),
            $accessor,
        );

        $this->populator = $populator;
    }

    /**
     * @throws PopulationException
     */
    public function populate(object $target, object $source, ?object $ctx = null): void
    {
        $this->populator->populate($target, $source, $ctx);
    }
}
