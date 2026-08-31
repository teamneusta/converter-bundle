<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Fixtures\Bundle\ExtendingBundle\Converter;

use Neusta\ConverterBundle\Context;
use Neusta\ConverterBundle\Converter;
use Neusta\ConverterBundle\TargetFactory;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * A converter with a constructor signature that is *not* the one of `GenericConverter` - exactly
 * the case the old `neusta_converter.converter.<id>.converter: <FQCN>` option could not express
 * (see issue #40).
 *
 * @template TSource of object
 * @template TTarget of object
 * @template TContext of object|null = null
 *
 * @implements Converter<TSource, TTarget, TContext>
 */
final class ReversingConverter implements Converter
{
    /**
     * @param TargetFactory<TTarget, TContext> $factory
     */
    public function __construct(
        private readonly TargetFactory $factory,
        private readonly string $sourceProperty,
        private readonly string $targetProperty,
        private readonly PropertyAccessorInterface $accessor,
    ) {
    }

    /**
     * @param TSource          $source
     * @param Context|TContext $ctx
     *
     * @return TTarget
     */
    public function convert(object $source, ?object $ctx = null): object
    {
        $target = $this->factory->create($ctx);
        $value = $this->accessor->getValue($source, $this->sourceProperty);

        $this->accessor->setValue($target, $this->targetProperty, \is_string($value) ? strrev($value) : $value);

        return $target;
    }
}
