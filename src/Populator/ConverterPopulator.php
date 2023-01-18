<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Populator;

use Neusta\ConverterBundle\Converter\Converter;
use Neusta\ConverterBundle\Exception\PopulationException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * A populator which uses a converter for an object of type S with a certain field
 * containing an object of type U which should be converted into V and populated into a field of T object.
 *
 * @template S of object
 * @template T of object
 * @template C of object
 * @implements Populator<S, T, C>
 */
class ConverterPopulator implements Populator
{
    private PropertyAccessorInterface $accessor;

    /**
     * @template U of object
     * @template V of object
     * @param Converter<U, V, C> $converter
     */
    public function __construct(
        private Converter $converter,
        private string $sourcePropertyName,
        private string $targetPropertyName,
        PropertyAccessorInterface $accessor = null,
    ) {
        $this->accessor = $accessor ?? PropertyAccess::createPropertyAccessor();
    }

    /**
     * @throws PopulationException
     */
    public function populate(object $target, object $source, ?object $ctx = null): void
    {
        try {
            $this->accessor->setValue($target, $this->targetPropertyName,
                $this->converter->convert($this->accessor->getValue($source, $this->sourcePropertyName), $ctx),
            );
        } catch (\Throwable $exception) {
            throw new PopulationException($this->sourcePropertyName, $this->targetPropertyName, $exception);
        }
    }
}
