<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Populator;

use Neusta\ConverterBundle\Exception\PopulationException;
use Neusta\ConverterBundle\Populator;
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
    private ?string $sourceArrayItemProperty;
    /** @var \Closure(mixed, TContext=):mixed|null */
    private ?\Closure $mapper;
    private PropertyAccessorInterface $arrayItemAccessor;
    private PropertyMappingPopulator $populator;

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
        $this->sourceArrayItemProperty = $sourceArrayItemProperty;
        $this->mapper = $mapper;
        $this->arrayItemAccessor = $arrayItemAccessor ?? PropertyAccess::createPropertyAccessor();
        $this->populator = new PropertyMappingPopulator(
            $targetProperty,
            $sourceArrayProperty,
            null,
            $this->unwrapAndMap(...),
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

    /**
     * @param TContext $ctx
     *
     * @return array<mixed>
     */
    private function unwrapAndMap(mixed $values, ?object $ctx = null): array
    {
        if (!\is_array($values) || [] === $values) {
            return [];
        }

        if (null === $this->sourceArrayItemProperty) {
            return $this->mapper ? array_map(fn ($item) => ($this->mapper)($item, $ctx), $values) : $values;
        }

        $mapper = fn ($item) => $this->arrayItemAccessor->getValue($item, $this->sourceArrayItemProperty);

        if ($this->mapper) {
            $mapper = fn ($item) => ($this->mapper)($mapper($item), $ctx);
        }

        return array_map($mapper, $values);
    }
}
