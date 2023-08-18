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
    private PropertyAccessorInterface $itemAccessor;
    private PropertyAccessorInterface $accessor;

    /**
     * @param \Closure(mixed, TContext=):mixed|null $mapper
     */
    public function __construct(
        private string $targetProperty,
        private string $sourceArrayProperty,
        private ?string $sourceArrayItemProperty = null,
        ?\Closure $mapper = null,
        PropertyAccessorInterface $itemAccessor = null,
        PropertyAccessorInterface $accessor = null,
    ) {
        $this->mapper = $mapper ?? static fn($v) => $v;
        $this->itemAccessor = $itemAccessor ?? PropertyAccess::createPropertyAccessor();
        $this->accessor = $accessor ?? PropertyAccess::createPropertyAccessor();
    }

    /**
     * @throws PopulationException
     */
    public function populate(object $target, object $source, ?object $ctx = null): void
    {
        try {
            $unwrappedArray = array_map(
                function ($arrayItem) {
                    if (!empty($this->sourceArrayItemProperty)) {
                        return $this->itemAccessor->getValue($arrayItem, $this->sourceArrayItemProperty);
                    }
                    return $arrayItem;
                },
                $this->accessor->getValue($source, $this->sourceArrayProperty)
            );


            $this->accessor->setValue(
                $target,
                $this->targetProperty,
                array_map(
                    function ($item) use ($ctx) {
                        return ($this->mapper)($item, $ctx);
                    },
                    $unwrappedArray
                )
            );
        } catch (\Throwable $exception) {
            throw new PopulationException($this->sourceArrayProperty, $this->targetProperty, $exception);
        }
    }

}
