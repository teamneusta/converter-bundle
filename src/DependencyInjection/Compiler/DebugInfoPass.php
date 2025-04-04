<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\DependencyInjection\Compiler;

use Neusta\ConverterBundle\Converter;
use Neusta\ConverterBundle\Debug\DebugInfo;
use Neusta\ConverterBundle\Debug\ServiceArgumentInfo;
use Neusta\ConverterBundle\Debug\ServiceInfo;
use Neusta\ConverterBundle\Populator;
use Neusta\ConverterBundle\TargetFactory;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class DebugInfoPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(DebugInfo::class)) {
            return;
        }

        $debugInfo = $container->findDefinition(DebugInfo::class);

        foreach ($container->getDefinitions() as $id => $definition) {
            if (!$reflection = $this->getClassReflection($container, $definition)) {
                continue;
            }

            $type = match (true) {
                $reflection->implementsInterface(Converter::class) => 'converter',
                $reflection->implementsInterface(Populator::class) => 'populator',
                $reflection->implementsInterface(TargetFactory::class) => 'factory',
                default => null,
            };

            if ($type) {
                $debugInfo->addMethodCall('add', [$type, $id, $this->getServiceInfo($definition, $reflection)]);
            }
        }
    }

    private function getClassReflection(ContainerBuilder $container, Definition $definition): ?\ReflectionClass
    {
        while ((null === $class = $definition->getClass()) && $definition instanceof ChildDefinition) {
            $definition = $container->findDefinition($definition->getParent());
        }

        if (null === $class) {
            return null;
        }

        return $container->getReflectionClass($class, false);
    }

    private function getServiceInfo(Definition $definition, \ReflectionClass $classReflection): Definition
    {
        $parametersReflection = $classReflection->getConstructor()?->getParameters();

        $argumentsInfo = [];
        foreach ($this->getArgumentInfo($definition->getArguments()) as $idOrName => $argument) {
            if (\is_int($idOrName) && $parametersReflection) {
                $argumentsInfo['$' . $parametersReflection[$idOrName]->name] = $argument;
            } else {
                $argumentsInfo[$idOrName] = $argument;
            }
        }

        return (new Definition(ServiceInfo::class))
            ->setArguments([$classReflection->name, $argumentsInfo]);
    }

    /**
     * @param array<mixed> $arguments
     *
     * @return array<Definition>
     */
    private function getArgumentInfo(array $arguments): array
    {
        return array_map(
            fn ($argument) => (new Definition(ServiceArgumentInfo::class))->setArguments(match (true) {
                $argument instanceof Reference => ['reference', '@' . $argument],
                \is_scalar($argument) => ['scalar', $argument],
                \is_array($argument) => ['array', $this->getArgumentInfo($argument)],
                \is_object($argument) => ['object', 'object(' . $argument::class . ')'],
                default => ['unknown', get_debug_type($argument)],
            }),
            $arguments,
        );
    }
}
