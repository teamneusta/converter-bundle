<?php
declare(strict_types=1);

namespace Neusta\ConverterBundle\Populator\Mapper;

use Neusta\ConverterBundle\Context;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * @template TContext of object|null = null
 */
final class ArrayPropertyMapper
{
    private PropertyAccessorInterface $arrayItemAccessor;

    /** @var \Closure(mixed, Context|TContext=):mixed|null */
    private readonly ?\Closure $mapper;

    /**
     * @param callable(mixed, Context|TContext=):mixed|null $mapper
     */
    public function __construct(
        private readonly ?string $sourceArrayItemProperty = null,
        ?callable $mapper = null,
        ?PropertyAccessorInterface $arrayItemAccessor = null,
    ) {
        $this->mapper = null !== $mapper ? $mapper(...) : null;
        $this->arrayItemAccessor = $arrayItemAccessor ?? PropertyAccess::createPropertyAccessor();
    }

    /**
     * @param Context|TContext $ctx
     *
     * @return array<mixed>
     */
    public function __invoke(mixed $values, ?object $ctx = null): array
    {
        if (!\is_array($values) || [] === $values) {
            return [];
        }

        if (null === $this->sourceArrayItemProperty) {
            return $this->mapper ? array_map(fn ($item) => ($this->mapper)($item, $ctx), $values) : $values;
        }

        $mapper = fn ($item) => $this->arrayItemAccessor->getValue($item, $this->sourceArrayItemProperty);

        if ($this->mapper) {
            $mapper = fn ($item) => ($this->mapper)($mapper($item), $ctx);
        }

        return array_map($mapper, $values);
    }
}
