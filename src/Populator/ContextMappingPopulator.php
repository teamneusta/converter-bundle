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
 * @template TContext of GenericContext|null = null
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
     * @param class-string|null                             $contextClass
     */
    public function __construct(
        private string $targetProperty,
        private string $contextProperty,
        ?\Closure $mapper = null,
        ?PropertyAccessorInterface $accessor = null,
        private ?string $contextClass = null,
        private bool $required = false,
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
            if ($this->required) {
                $this->fail('No context was provided.');
            }

            return;
        }

        if ($ctx instanceof GenericContext) {
            if (!$ctx->hasKey($this->contextProperty)) {
                if ($this->required) {
                    $this->fail(\sprintf('The context does not contain a value for "%s".', $this->contextProperty));
                }

                return;
            }

            $readValue = fn () => $ctx->getValue($this->contextProperty);
        } elseif ($ctx instanceof Context) {
            if (!isset($this->contextClass)) {
                throw new \LogicException('The relevant context class is not set.');
            }

            if (null === $contextObject = $ctx->tryGet($this->contextClass)) {
                if ($this->required) {
                    $this->fail(\sprintf('The context does not contain an instance of "%s".', $this->contextClass));
                }

                return;
            }

            $readValue = fn () => $this->accessor->getValue($contextObject, $this->contextProperty);
        } else {
            throw new \InvalidArgumentException(\sprintf('Invalid context type "%s".', $ctx::class));
        }

        try {
            $this->accessor->setValue($target, $this->targetProperty, ($this->mapper)($readValue(), $ctx));
        } catch (\Throwable $exception) {
            throw new PopulationException($this->contextProperty, $this->targetProperty, $exception);
        }
    }

    /**
     * @throws PopulationException
     */
    private function fail(string $message): never
    {
        throw new PopulationException($this->contextProperty, $this->targetProperty, new \RuntimeException($message));
    }
}
