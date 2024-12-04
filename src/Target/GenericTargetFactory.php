<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Target;

use Neusta\ConverterBundle\TargetFactory;

/**
 * @template T of object
 *
 * @implements TargetFactory<T, object|null>
 */
final class GenericTargetFactory implements TargetFactory
{
    /** @var \ReflectionClass<T> */
    private \ReflectionClass $type;

    /**
     * @param class-string<T> $type
     *
     * @throws \ReflectionException
     * @throws \InvalidArgumentException
     */
    public function __construct(string $type)
    {
        $this->type = new \ReflectionClass($type);

        if (!$this->type->isInstantiable()) {
            throw new \InvalidArgumentException(\sprintf('Target class "%s" is not instantiable.', $type));
        }

        if ($this->type->getConstructor()?->getNumberOfRequiredParameters()) {
            throw new \InvalidArgumentException(\sprintf('Target class "%s" has required constructor parameters.', $type));
        }
    }

    /**
     * @throws \ReflectionException
     */
    public function create(?object $ctx = null): object
    {
        return $this->type->newInstance();
    }
}
