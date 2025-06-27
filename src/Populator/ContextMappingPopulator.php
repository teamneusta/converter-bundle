<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Populator;

use Neusta\ConverterBundle\Context;
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
    /** @var \Closure(mixed, Context|TContext=):mixed */
    private \Closure $mapper;
    private PropertyAccessorInterface $accessor;

    /**
     * @param \Closure(mixed, Context|TContext=):mixed|null $mapper
     * @param class-string|null                             $contextObjectType
     */
    public function __construct(
        private string $targetProperty,
        private string $contextProperty,
        ?\Closure $mapper = null,
        ?PropertyAccessorInterface $accessor = null,
        private ?string $contextObjectType = null,
    ) {
        $this->mapper = $mapper ?? static fn ($v) => $v;
        $this->accessor = $accessor ?? PropertyAccess::createPropertyAccessor();
    }

    /**
     * @throws PopulationException
     */
    public function populate(object $target, object $source, ?object $ctx = null): void
    {
        if (!$ctx) {
            return;
        }

        if ($ctx instanceof GenericContext) {
            trigger_deprecation(
                'teamneusta/converter-bundle',
                '1.10.0',
                'Passing a "%s" is deprecated, pass a "%s" and set the relevant "contextObject" type in the constructor instead.',
                GenericContext::class,
                Context::class,
            );

            if (!$ctx->hasKey($this->contextProperty)) {
                return;
            }

            $value = $ctx->getValue($this->contextProperty);
        } elseif ($ctx instanceof Context) {
            if (!isset($this->contextObjectType)) {
                throw new \LogicException('The relevant context object type is not set.');
            }

            if (!$contextObject = $ctx->get($this->contextObjectType)) {
                return;
            }

            $value = $this->accessor->getValue($contextObject, $this->contextProperty);
        } else {
            throw new \InvalidArgumentException(\sprintf('Invalid context type "%s".', $ctx::class));
        }

        try {
            $this->accessor->setValue($target, $this->targetProperty, ($this->mapper)($value, $ctx));
        } catch (\Throwable $exception) {
            throw new PopulationException($this->contextProperty, $this->targetProperty, $exception);
        }
    }
}
