<?php
declare(strict_types=1);

namespace Neusta\ConverterBundle\Populator;

use Neusta\ConverterBundle\Converter\Context\GenericContext;
use Neusta\ConverterBundle\Populator;
use Neusta\ConverterBundle\Populator\CustomContract\Context;
use Neusta\ConverterBundle\Populator\CustomContract\Populator as PopulatorAttribute;
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
    /**
     * @param \Closure(object, object, object|null):void $populator
     * @param list<'source'|'target'|'context'>          $parameterOrder
     */
    public function __construct(
        private readonly \Closure $populator,
        private readonly array $parameterOrder,
    ) {
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

        ($this->populator)(...$args);
    }

    /**
     * @internal Used by {@see CustomContractPopulatorPass} at compile time.
     */
    public static function resolvePopulateMethod(\ReflectionClass $class): \ReflectionMethod
    {
        $contract = self::findPopulatorContract($class);
        $methods = $contract->getMethods();

        if (1 === \count($methods)) {
            return $class->getMethod($methods[0]->getName());
        }

        $attributed = array_values(array_filter(
            $methods,
            static fn (\ReflectionMethod $m) => [] !== $m->getAttributes(PopulatorAttribute::class),
        ));

        if (1 !== \count($attributed)) {
            throw new \LogicException(sprintf(
                'The populator "%s" has multiple methods. Exactly one must be annotated with #[Populator].',
                $contract->getName(),
            ));
        }

        return $class->getMethod($attributed[0]->getName());
    }

    /**
     * Todo: can we cache this for known classes!?
     *
     * @internal Used by {@see CustomContractPopulatorPass} at compile time.
     *
     * @return list<'source'|'target'|'context'>
     */
    public static function resolveParameterOrder(\ReflectionMethod $method): array
    {
        $parameterOrder = [];
        foreach ($method->getParameters() as $parameter) {
            if ([] !== $parameter->getAttributes(Source::class)) {
                $parameterOrder[] = 'source';
            } elseif ([] !== $parameter->getAttributes(Target::class)) {
                $parameterOrder[] = 'target';
            } elseif ([] !== $parameter->getAttributes(Context::class)) {
                // Todo: wenn es das gibt, dann muss es vom Typ `GenericContext|null` sein und einen default Wert haben!
                $parameterOrder[] = 'context';
            } else {
                throw new \LogicException(sprintf(
                    'Parameter "$%s" of method "%s::%s" must be annotated with #[Source], #[Target] or #[Context].',
                    $parameter->getName(),
                    $method->getDeclaringClass()->getName(),
                    $method->getName(),
                ));
            }
        }

        if (!\in_array('source', $parameterOrder, true) || !\in_array('target', $parameterOrder, true)) {
            throw new \LogicException(sprintf(
                'Method "%s::%s" must have parameters annotated with both #[Source] and #[Target].',
                $method->getDeclaringClass()->getName(),
                $method->getName(),
            ));
        }

        return $parameterOrder;
    }

    // Todo: can we cache this for known classes!?
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
