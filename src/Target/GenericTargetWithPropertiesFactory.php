<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Target;

use Neusta\ConverterBundle\TargetFactory;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * @template T of object
 *
 * @implements TargetFactory<T, object|null>
 */
final class GenericTargetWithPropertiesFactory implements TargetFactory
{
    /** @var GenericTargetFactory<T> */
    private GenericTargetFactory $genericTargetFactory;

    private PropertyAccessorInterface $propertyAccessor;

    /** @var array<string, mixed> */
    private array $properties;

    /**
     * @param class-string<T>      $type
     * @param array<string, mixed> $properties
     *
     * @throws \ReflectionException
     */
    public function __construct(
        string $type,
        array $properties,
        ?PropertyAccessorInterface $propertyAccessor = null,
    ) {
        $this->genericTargetFactory = new GenericTargetFactory($type);
        $this->properties = $properties;
        $this->propertyAccessor = $propertyAccessor ?? PropertyAccess::createPropertyAccessor();
    }

    /**
     * @throws \LogicException
     */
    public function create(?object $ctx = null): object
    {
        $target = $this->genericTargetFactory->create();
        foreach ($this->properties as $property => $value) {
            try {
                $this->propertyAccessor->setValue($target, $property, $value);
            } catch (\Exception $e) {
                throw new \LogicException(\sprintf('Cannot set property "%s" on target "%s" because: %s', $property, $target::class, $e->getMessage()), 0, $e);
            }
        }

        return $target;
    }
}
