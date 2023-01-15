<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Populator;

/**
 * @template S of object
 * @template T of object
 * @template C of object
 * @implements Populator<S, T, C>
 */
class SamePropertyPopulator implements Populator
{
    public function __construct(
        private string $propertyName,
    ) {
    }

    public function populate(object $target, object $source, ?object $ctx = null): void
    {
        $valueToSet = null;
        $valueHasBeenSet = false;

        foreach (['get', 'is', 'has'] as $prefix) {
            if (method_exists($source, $prefix . ucfirst($this->propertyName))) {
                $valueToSet = $source->{$prefix . ucfirst($this->propertyName)}();
                $valueHasBeenSet = true;
                break;
            }
        }

        if ($valueHasBeenSet
            && method_exists($target, 'set' . ucfirst($this->propertyName))
            && $this->isSameType($target, $source)
        ) {
            $target->{'set' . ucfirst($this->propertyName)}($valueToSet);
        }
    }

    private function isSameType(object $target, object $source): bool
    {
        $sourcePropertyType = (new \ReflectionProperty($source, $this->propertyName))->getType();
        $targetPropertyType = (new \ReflectionProperty($target, $this->propertyName))->getType();
        return ($sourcePropertyType instanceof \ReflectionNamedType)
            && ($targetPropertyType instanceof \ReflectionNamedType)
            && $sourcePropertyType->getName() === $targetPropertyType->getName();
    }
}
