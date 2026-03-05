<?php
declare(strict_types=1);

namespace Neusta\ConverterBundle\Populator\CustomContract;

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

        if (1 !== \count($methods)) {
            throw new \LogicException(sprintf(
                'Custom populator contract interface "%s" must declare exactly one method.',
                $contract->name,
            ));
        }

        return $cache[$contract->name] = new self(
            $methods[0]->name,
            ParameterOrder::fromReflection($methods[0]),
        );
    }

    private static function findContract(\ReflectionClass $class): \ReflectionClass
    {
        static $cache = [];

        if (isset($cache[$class->name])) {
            return $cache[$class->name];
        }

        $candidates = array_values(array_filter($class->getInterfaces(), self::isContract(...)));

        if ([] === $candidates) {
            throw new \LogicException(sprintf(
                'Class "%s" does not implement a custom populator contract interface.',
                $class->name,
            ));
        }

        if (1 < \count($candidates)) {
            throw new \LogicException(sprintf(
                'Class "%s" implements multiple custom populator contract interfaces: %s.',
                $class->name,
                implode(', ', array_column($candidates, 'name')),
            ));
        }

        return $cache[$class->name] = $candidates[0];
    }

    private static function isContract(\ReflectionClass $class): bool
    {
        if (!$class->isInterface()) {
            return false;
        }

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
}
