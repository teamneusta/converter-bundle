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
final class PropertyMappingPopulator implements Populator
{
    /** @var \Closure(mixed, TContext=):mixed */
    private \Closure $mapper;
    private PropertyAccessorInterface $accessor;

    /**
     * @param \Closure(mixed, TContext=):mixed|null $mapper
     */
    public function __construct(
        private string $targetProperty,
        private string $sourceProperty,
        private mixed $defaultValue = null,
        ?\Closure $mapper = null,
        ?PropertyAccessorInterface $accessor = null,
        private bool $skipNull = false,
    ) {
        $this->mapper = $mapper ?? static fn ($v) => $v;
        $this->accessor = $accessor ?? PropertyAccess::createPropertyAccessor();
    }

    /**
     * @throws PopulationException
     */
    public function populate(object $target, object $source, ?object $ctx = null): void
    {
        try {
            if ('$this' !== $this->sourceProperty) {
                $sourcePropertyValue = $this->accessor->getValue($source, $this->sourceProperty);
            } else {
                $sourcePropertyValue = $source;
            }

            $value = $sourcePropertyValue ?? $this->defaultValue;

            if (!$this->skipNull || (null !== $value)) {
                $this->accessor->setValue($target, $this->targetProperty, ($this->mapper)($value, $ctx));
            }
        } catch (\Throwable $exception) {
            throw new PopulationException($this->sourceProperty, $this->targetProperty, $exception);
        }
    }
}
