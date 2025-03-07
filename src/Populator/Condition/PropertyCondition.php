<?php
declare(strict_types=1);

namespace Neusta\ConverterBundle\Populator\Condition;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * @template TSource of object
 * @template TTarget of object
 * @template TContext of object|null
 */
final class PropertyCondition
{
    private PropertyAccessorInterface $accessor;

    public function __construct(
        private string $propertyName,
        private string $propertyBase,
        private mixed $expectedValue,
        ?PropertyAccessorInterface $accessor = null,
    ) {
        $this->accessor = $accessor ?? PropertyAccess::createPropertyAccessor();
    }

    /**
     * @param TTarget  $target
     * @param TSource  $source
     * @param TContext $ctx
     */
    public function __invoke(object $target, object $source, ?object $ctx = null): bool
    {
        $objectToCheck = 'target' === $this->propertyBase ? $target : $source;

        return property_exists($objectToCheck, $this->propertyName)
            && $this->accessor->getValue($objectToCheck, $this->propertyName) === $this->expectedValue;
    }
}
