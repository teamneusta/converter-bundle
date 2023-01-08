<?php

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
        private string $propertyName
    )
    {
    }

    /**
     * @inheritDoc
     */
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

        if ($valueHasBeenSet && method_exists($target, 'set' . ucfirst($this->propertyName))) {
            $target->{'set' . ucfirst($this->propertyName)}($valueToSet);
        }
    }
}