<?php
declare(strict_types=1);

namespace Neusta\ConverterBundle\Populator\CustomContract;

use Neusta\ConverterBundle\Populator\CustomContract\Attribute\AsPopulatorContract;

/**
 * @internal
 */
final class PopulatorContract
{
    private function __construct(
        public readonly string $methodName,
        public readonly ParameterOrder $parameterOrder,
    ) {
    }

    /**
     * Whether the given class implements a custom populator contract at all.
     *
     * This is a cheap check that never throws, so that it can be used to
     * detect contract populators among arbitrary classes. Use
     * {@see self::fromReflection()} to actually resolve (and validate) it.
     */
    public static function isImplementedBy(\ReflectionClass $class): bool
    {
        foreach ($class->getInterfaces() as $interface) {
            if (self::isContract($interface)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolves the custom populator contract implemented by the given class.
     *
     * @throws \LogicException if the class does not implement exactly one valid contract
     */
    public static function fromReflection(\ReflectionClass $class): self
    {
        if (!$contract = self::findContract($class)) {
            throw new \LogicException(\sprintf(
                'Class "%s" does not implement a custom populator contract interface. '
                . 'Expected an implemented interface annotated with #[%s].',
                $class->name,
                AsPopulatorContract::class,
            ));
        }

        return self::fromContract($contract);
    }

    private static function fromContract(\ReflectionClass $contract): self
    {
        static $cache = [];

        if (isset($cache[$contract->name])) {
            return $cache[$contract->name];
        }

        // Note: inherited methods count as well, so extending a contract with an
        // additional method makes it ambiguous - and therefore invalid.
        $methods = $contract->getMethods();

        if (1 !== \count($methods)) {
            throw new \LogicException(\sprintf(
                'Custom populator contract interface "%s" must declare exactly one method, got %d: %s.',
                $contract->name,
                \count($methods),
                implode(', ', array_map(static fn (\ReflectionMethod $method) => $method->name, $methods)),
            ));
        }

        return $cache[$contract->name] = new self(
            $methods[0]->name,
            ParameterOrder::fromReflection($methods[0]),
        );
    }

    /**
     * @throws \LogicException if the class implements more than one contract
     */
    private static function findContract(\ReflectionClass $class): ?\ReflectionClass
    {
        $candidates = self::onlyMostDerived(array_filter($class->getInterfaces(), self::isContract(...)));

        if ([] === $candidates) {
            return null;
        }

        if (1 < \count($candidates)) {
            throw new \LogicException(\sprintf(
                'Class "%s" implements multiple custom populator contract interfaces: %s.',
                $class->name,
                implode(', ', array_keys($candidates)),
            ));
        }

        return reset($candidates);
    }

    private static function isContract(\ReflectionClass $interface): bool
    {
        return [] !== $interface->getAttributes(AsPopulatorContract::class);
    }

    /**
     * Drops candidates that are extended by another candidate, so that an
     * interface hierarchy does not count as multiple contracts.
     *
     * @param array<string, \ReflectionClass> $candidates
     *
     * @return array<string, \ReflectionClass>
     */
    private static function onlyMostDerived(array $candidates): array
    {
        return array_filter($candidates, static function (string $name) use ($candidates): bool {
            foreach ($candidates as $otherName => $other) {
                if ($otherName !== $name && $other->isSubclassOf($name)) {
                    return false;
                }
            }

            return true;
        }, \ARRAY_FILTER_USE_KEY);
    }
}
