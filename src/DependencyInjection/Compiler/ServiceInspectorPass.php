<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\DependencyInjection\Compiler;

use Neusta\ConverterBundle\Converter;
use Neusta\ConverterBundle\Dump\InspectedServicesRegistry;
use Neusta\ConverterBundle\Dump\ServiceArgumentInfo;
use Neusta\ConverterBundle\Dump\ServiceInfo;
use Neusta\ConverterBundle\Populator;
use Neusta\ConverterBundle\TargetFactory;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class ServiceInspectorPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(InspectedServicesRegistry::class)) {
            return;
        }

        $registry = $container->findDefinition(InspectedServicesRegistry::class);

        foreach ($container->getDefinitions() as $id => $definition) {
            if (!$reflection = $this->getClassReflection($container, $definition)) {
                continue;
            }

            if ($reflection->implementsInterface(Converter::class)) {
                $registry->addMethodCall('addConverter', [$id, $this->getServiceInfo($definition, $reflection)]);
            } elseif ($reflection->implementsInterface(Populator::class)) {
                $registry->addMethodCall('addPopulator', [$id, $this->getServiceInfo($definition, $reflection)]);
            } elseif ($reflection->implementsInterface(TargetFactory::class)) {
                $registry->addMethodCall('addTargetFactory', [$id, $this->getServiceInfo($definition, $reflection)]);
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
        return (new Definition(ServiceInfo::class))
            ->setArguments([$classReflection->name, $this->getArgumentInfo($definition->getArguments())]);
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
                default => ['unknown', 'unknown'],
            }),
            $arguments,
        );
    }
}
