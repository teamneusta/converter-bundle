<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Target;

use Neusta\ConverterBundle\TargetFactory;

/**
 * @template T of object
 *
 * @implements TargetFactory<T, object>
 */
final class GenericTargetFactory implements TargetFactory
{
    /** @var \ReflectionClass<T> */
    private \ReflectionClass $type;

    /**
     * @param class-string<T> $type
     */
    public function __construct(string $type)
    {
        $this->type = new \ReflectionClass($type);

        if (!$this->type->isInstantiable()) {
            throw new \InvalidArgumentException(sprintf('Target class "%s" is not instantiable.', $type));
        }

        if ($this->type->getConstructor()?->getNumberOfRequiredParameters()) {
            throw new \InvalidArgumentException(sprintf('Target class "%s" has required constructor parameters.', $type));
        }
    }

    public function create(?object $ctx = null): object
    {
        try {
            return $this->type->newInstance();
        } catch (\ReflectionException $e) {
            throw new \LogicException(sprintf('Cannot create new instance of "%s" because: %s', $this->type->getName(), $e->getMessage()), 0, $e);
        }
    }
}
