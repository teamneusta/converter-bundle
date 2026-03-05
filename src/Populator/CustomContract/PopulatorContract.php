<?php
declare(strict_types=1);

namespace Neusta\ConverterBundle\Populator\CustomContract;

/**
 * @internal
 */
final class PopulatorContract
{
    private function __construct(
        public readonly string $methodName,
        public readonly ParameterOrder $parameterOrder,
    ) {}

    public static function fromReflection(\ReflectionClass $class): self
    {
        $contract = self::findContract($class);
        $methods = $contract->getMethods();

        if (1 === \count($methods)) {
            return new self(
                $methods[0]->getName(),
                ParameterOrder::fromReflection($methods[0]),
            );
        }

        if (1 === \count($attributed = array_values(array_filter($methods, self::hasPopulatorAttribute(...))))) {
            return new self(
                $attributed[0]->getName(),
                ParameterOrder::fromReflection($attributed[0]),
            );
        }

        throw new \LogicException(sprintf(
            'The populator "%s" has multiple methods. Exactly one must be annotated with #[Populator].',
            $contract->getName(),
        ));
    }

    // Todo: can we cache this for known classes!?
    private static function findContract(\ReflectionClass $class): \ReflectionClass
    {
        $current = $class;

        while ($current) {
            if (self::isContract($current)) {
                return $current;
            }

            $current = $current->getParentClass();
        }

        throw new \LogicException(sprintf(
            'Class "%s" does not implement a custom populator contract.',
            $class->getName(),
        ));
    }

    private static function isContract(\ReflectionClass $class): bool
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

    private static function hasPopulatorAttribute(\ReflectionMethod $method): bool
    {
        return [] !== $method->getAttributes(Populator::class);
    }
}
