<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Populator;

use Neusta\ConverterBundle\Exception\PopulationException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * @template S of object
 * @template T of object
 * @template C of object
 * @implements Populator<S, T, C>
 */
final class MappedPropertyPopulator implements Populator
{
    private PropertyAccessorInterface $accessor;
    private \Closure $mapper;

    /**
     * @param \Closure(mixed, C):mixed|null $mapper
     */
    public function __construct(
        private string $targetProperty,
        private string $sourceProperty,
        ?\Closure $mapper = null,
        PropertyAccessorInterface $accessor = null,
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
            $this->accessor->setValue($target, $this->targetProperty,
                ($this->mapper)($this->accessor->getValue($source, $this->sourceProperty), $ctx),
            );
        } catch (\Throwable $exception) {
            throw new PopulationException($this->sourceProperty, $this->targetProperty, $exception);
        }
    }
}
