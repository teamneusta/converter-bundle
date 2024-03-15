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
    /** @var \Closure(mixed, TContext=):mixed */
    private \Closure $mapper;
    private PropertyAccessorInterface $arrayItemAccessor;
    private PropertyAccessorInterface $accessor;

    /**
     * @param \Closure(mixed, TContext=):mixed|null $mapper
     */
    public function __construct(
        private string $targetProperty,
        private string $sourceArrayProperty,
        private ?string $sourceArrayItemProperty = null,
        ?\Closure $mapper = null,
        ?PropertyAccessorInterface $arrayItemAccessor = null,
        ?PropertyAccessorInterface $accessor = null,
    ) {
        $this->mapper = $mapper ?? static fn ($v) => $v;
        $this->arrayItemAccessor = $arrayItemAccessor ?? PropertyAccess::createPropertyAccessor();
        $this->accessor = $accessor ?? PropertyAccess::createPropertyAccessor();
    }

    /**
     * @throws PopulationException
     */
    public function populate(object $target, object $source, ?object $ctx = null): void
    {
        try {
            $sourceArrayPropertyValues = $this->accessor->getValue($source, $this->sourceArrayProperty);

            $unwrappedArray = [];
            if (\is_array($sourceArrayPropertyValues) && [] !== $sourceArrayPropertyValues) {
                $unwrappedArray = array_map(
                    fn ($item) => null !== $this->sourceArrayItemProperty
                        ? $this->arrayItemAccessor->getValue($item, $this->sourceArrayItemProperty)
                        : $item,
                    $sourceArrayPropertyValues,
                );
            }

            $this->accessor->setValue(
                $target,
                $this->targetProperty,
                array_map(fn ($item) => ($this->mapper)($item, $ctx), $unwrappedArray),
            );
        } catch (\Throwable $exception) {
            throw new PopulationException($this->sourceArrayProperty, $this->targetProperty, $exception);
        }
    }
}
