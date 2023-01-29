<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Populator;

use Neusta\ConverterBundle\Converter;
use Neusta\ConverterBundle\Exception\PopulationException;
use Neusta\ConverterBundle\Populator;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * A populator that uses a converter to convert a specific field of type TInnerSource from TSource
 * into an object of type TInnerTarget for a field of TTarget.
 *
 * @template TSource of object
 * @template TTarget of object
 * @template TContext of object|null
 *
 * @implements Populator<TSource, TTarget, TContext>
 */
final class ConverterPopulator implements Populator
{
    private MappedPropertyPopulator $populator;

    /**
     * @template TInnerSource of object
     * @template TInnerTarget of object
     *
     * @param Converter<TInnerSource, TInnerTarget, TContext> $converter
     */
    public function __construct(
        Converter $converter,
        string $sourcePropertyName,
        string $targetPropertyName,
        PropertyAccessorInterface $accessor = null,
    ) {
        $this->populator = new MappedPropertyPopulator(
            $targetPropertyName,
            $sourcePropertyName,
            \Closure::fromCallable([$converter, 'convert']),
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
