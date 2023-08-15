<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Populator;

use Neusta\ConverterBundle\Converter;
use Neusta\ConverterBundle\Exception\PopulationException;
use Neusta\ConverterBundle\Populator;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * A populator that uses a converter to convert a field of type array<TInnerSource> from TSource
 * into an object of type array<TInnerTarget> for a field of TTarget.
 *
 * @template TSource of object
 * @template TTarget of object
 * @template TContext of object|null
 *
 * @implements Populator<TSource, TTarget, TContext>
 */
final class ArrayConvertingPopulator implements Populator
{
    private ArrayPropertyMappingPopulator $populator;

    /**
     * @template TInnerSource of object
     * @template TInnerTarget of object
     *
     * @param Converter<TInnerSource, TInnerTarget, TContext> $converter
     */
    public function __construct(
        Converter                 $converter,
        string                    $sourceArrayPropertyName,
        string                    $targetPropertyName,
        ?string                   $sourceArrayItemPropertyName = null,
        PropertyAccessorInterface $itemAccessor = null,
        PropertyAccessorInterface $accessor = null,
    )
    {
        $this->populator = new ArrayPropertyMappingPopulator(
            $targetPropertyName,
            $sourceArrayPropertyName,
            $sourceArrayItemPropertyName,
            \Closure::fromCallable([$converter, 'convert']),
            $itemAccessor,
            $accessor,
        );
    }

    /**
     * @throws PopulationException
     */
    public function populate(object $target, object $source, ?object $ctx = null): void
    {
        $this->populator->populate($target, $source, $ctx);
    }
}
