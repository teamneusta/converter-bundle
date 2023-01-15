<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Populator;

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

    public function __construct(
        private string $targetProperty,
        private string $sourceProperty,
        PropertyAccessorInterface $accessor = null,
    ) {
        $this->accessor = $accessor ?? PropertyAccess::createPropertyAccessor();
    }

    public function populate(object $target, object $source, ?object $ctx = null): void
    {
        $this->accessor->setValue($target, $this->targetProperty,
            $this->accessor->getValue($source, $this->sourceProperty),
        );
    }
}
