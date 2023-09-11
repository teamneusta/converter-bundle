<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Populator;

use Neusta\ConverterBundle\Converter\Context\GenericContext;
use Neusta\ConverterBundle\Exception\PopulationException;
use Neusta\ConverterBundle\Populator;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * @template TSource of object
 * @template TTarget of object
 * @template TContext of GenericContext|null
 *
 * @implements Populator<TSource, TTarget, TContext>
 */
final class ContextMappingPopulator implements Populator
{
    /** @var \Closure(mixed, TContext=):mixed */
    private \Closure $mapper;
    private PropertyAccessorInterface $accessor;

    /**
     * @param \Closure(mixed, TContext=):mixed|null $mapper
     */
    public function __construct(
        private string $targetProperty,
        private string $contextProperty,
        \Closure $mapper = null,
        PropertyAccessorInterface $accessor = null,
    ) {
        $this->mapper = $mapper ?? static fn ($v) => $v;
        $this->accessor = $accessor ?? PropertyAccess::createPropertyAccessor();
    }

    /**
     * @throws PopulationException
     */
    public function populate(object $target, object $source, object $ctx = null): void
    {
        if (!$ctx || !$ctx->hasKey($this->contextProperty)) {
            return;
        }

        try {
            $this->accessor->setValue(
                $target,
                $this->targetProperty,
                ($this->mapper)($ctx->getValue($this->contextProperty), $ctx)
            );
        } catch (\Throwable $exception) {
            throw new PopulationException($this->contextProperty, $this->targetProperty, $exception);
        }
    }
}
