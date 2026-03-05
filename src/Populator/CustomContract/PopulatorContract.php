<?php
declare(strict_types=1);

namespace Neusta\ConverterBundle\Populator\CustomContract;

use Neusta\ConverterBundle\Populator\CustomContract\Attribute\Populator;
use Neusta\ConverterBundle\Populator\CustomContract\Attribute\Source;
use Neusta\ConverterBundle\Populator\CustomContract\Attribute\Target;

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
        static $cache = [];

        $contract = self::findContract($class);

        if (isset($cache[$contract->name])) {
            return $cache[$contract->name];
        }

        $methods = $contract->getMethods();

        if (1 === \count($methods)) {
            return $cache[$contract->name] = new self(
                $methods[0]->name,
                ParameterOrder::fromReflection($methods[0]),
            );
        }

        if (1 === \count($attributed = array_values(array_filter($methods, self::hasPopulatorAttribute(...))))) {
            return $cache[$contract->name] = new self(
                $attributed[0]->name,
                ParameterOrder::fromReflection($attributed[0]),
            );
        }

        throw new \LogicException(sprintf(
            'The populator "%s" has multiple methods. Exactly one must be annotated with #[Populator].',
            $contract->name,
        ));
    }

    private static function findContract(\ReflectionClass $class): \ReflectionClass
    {
        static $cache = [];

        if (isset($cache[$class->name])) {
            return $cache[$class->name];
        }

        $current = $class;

        while ($current) {
            if (self::isContract($current)) {
                return $cache[$class->name] = $current;
            }

            $current = $current->getParentClass();
        }

        throw new \LogicException(sprintf(
            'Class "%s" does not implement a custom populator contract.',
            $class->name,
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

                    continue;
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
