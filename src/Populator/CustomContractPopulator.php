<?php
declare(strict_types=1);

namespace Neusta\ConverterBundle\Populator;

use Neusta\ConverterBundle\Populator;
use Neusta\ConverterBundle\Populator\CustomContract\Context;
use Neusta\ConverterBundle\Populator\CustomContract\Populator as PopulatorMethod;
use Neusta\ConverterBundle\Populator\CustomContract\Source;
use Neusta\ConverterBundle\Populator\CustomContract\Target;

/**
 * @template TSource of object
 * @template TTarget of object
 * @template TContext of object|null
 *
 * @implements Populator<TSource, TTarget, TContext>
 */
final class CustomContractPopulator implements Populator
{
    private \ReflectionMethod $method;

    /** @var list<'source'|'target'|'context'> */
    private array $parameterOrder;

    public function __construct(
        private readonly object $populator,
    ) {
        [$this->method, $this->parameterOrder] = self::resolvePopulateMethod(new \ReflectionObject($populator));
    }

    public function populate(object $target, object $source, ?object $ctx = null): void
    {
        $args = [];
        foreach ($this->parameterOrder as $role) {
            $args[] = match ($role) {
                'source' => $source,
                'target' => $target,
                'context' => $ctx,
            };
        }

        $this->method->invoke($this->populator, ...$args);
    }

    /**
     * @return array{\ReflectionMethod, list<'source'|'target'|'context'>}
     */
    private static function resolvePopulateMethod(\ReflectionClass $class): array
    {
        $contract = self::findPopulatorContract($class);
        $methods = $contract->getMethods();

        if (1 === \count($methods)) {
            $method = $methods[0];
        } else {
            $attributed = array_values(array_filter(
                $methods,
                static fn (\ReflectionMethod $m) => [] !== $m->getAttributes(PopulatorMethod::class),
            ));

            if (1 !== \count($attributed)) {
                throw new \LogicException(sprintf(
                    'The populator "%s" has multiple methods. Exactly one must be annotated with #[Populator].',
                    $contract->getName(),
                ));
            }

            $method = $attributed[0];
        }

        // Resolve the method on the actual class to call it
        $concreteMethod = $class->getMethod($method->getName());

        $parameterOrder = [];
        foreach ($concreteMethod->getParameters() as $parameter) {
            if ([] !== $parameter->getAttributes(Source::class)) {
                $parameterOrder[] = 'source';
            } elseif ([] !== $parameter->getAttributes(Target::class)) {
                $parameterOrder[] = 'target';
            } elseif ([] !== $parameter->getAttributes(Context::class)) {
                $parameterOrder[] = 'context';
            } else {
                throw new \LogicException(sprintf(
                    'Parameter "$%s" of method "%s::%s" must be annotated with #[Source], #[Target] or #[Context].',
                    $parameter->getName(),
                    $class->getName(),
                    $method->getName(),
                ));
            }
        }

        if (!\in_array('source', $parameterOrder, true) || !\in_array('target', $parameterOrder, true)) {
            throw new \LogicException(sprintf(
                'Method "%s::%s" must have parameters annotated with both #[Source] and #[Target].',
                $class->getName(),
                $method->getName(),
            ));
        }

        return [$concreteMethod, $parameterOrder];
    }

    private static function findPopulatorContract(\ReflectionClass $class): \ReflectionClass
    {
        $current = $class;

        while ($current) {
            if (self::isPopulatorContract($current)) {
                return $current;
            }

            $current = $current->getParentClass();
        }

        throw new \LogicException(sprintf(
            'Class "%s" does not implement a custom populator contract.',
            $class->getName(),
        ));
    }

    private static function isPopulatorContract(\ReflectionClass $class): bool
    {
        foreach ($class->getMethods() as $method) {
            $hasSource = false;
            $hasTarget = false;

            foreach ($method->getParameters() as $parameter) {
                if ([] !== $parameter->getAttributes(Source::class)) {
                    $hasSource = true;
                }
                if ([] !== $parameter->getAttributes(Target::class)) {
                    $hasTarget = true;
                }
            }

            if ($hasSource && $hasTarget) {
                return true;
            }
        }

        return false;
    }
}
