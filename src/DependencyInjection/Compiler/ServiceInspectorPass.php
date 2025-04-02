<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\DependencyInjection\Compiler;

use Neusta\ConverterBundle\Converter;
use Neusta\ConverterBundle\Dump\InspectedServicesRegistry;
use Neusta\ConverterBundle\Populator;
use Neusta\ConverterBundle\TargetFactory;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @phpstan-import-type ServiceArgumentsType from InspectedServicesRegistry
 */
final class ServiceInspectorPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(InspectedServicesRegistry::class)) {
            return;
        }

        $registry = $container->findDefinition(InspectedServicesRegistry::class);

        foreach ($container->getDefinitions() as $id => $definition) {
            $arguments = $definition->getArguments();

            while ((null === $class = $definition->getClass()) && $definition instanceof ChildDefinition) {
                $definition = $container->findDefinition($definition->getParent());
            }

            if (null === $class) {
                continue;
            }

            if (!$reflection = $container->getReflectionClass($class, false)) {
                continue;
            }

            if ($reflection->implementsInterface(Converter::class)) {
                $registry->addMethodCall('addConverter', [$id, $class, $this->handleArguments($arguments)]);
            } elseif ($reflection->implementsInterface(Populator::class)) {
                $registry->addMethodCall('addPopulator', [$id, $class, $this->handleArguments($arguments)]);
            } elseif ($reflection->implementsInterface(TargetFactory::class)) {
                $registry->addMethodCall('TargetFactory', [$id, $class, $this->handleArguments($arguments)]);
            }
        }
    }

    /**
     * @param array<int|string, mixed|array<mixed>> $inputArguments
     *
     * @return ServiceArgumentsType
     */
    private function handleArguments(array $inputArguments): array
    {
        return array_map(fn ($argument) => match (true) {
            $argument instanceof Reference => [
                'type' => 'reference',
                'value' => '@' . $argument,
            ],
            \is_scalar($argument) => [
                'type' => 'scalar',
                'value' => $argument,
            ],
            \is_array($argument) => [
                'type' => 'array',
                'value' => $this->handleArguments($argument),
            ],
            \is_object($argument) => [
                'type' => 'object',
                'value' => 'object(' . $argument::class . ')',
            ],
            default => [
                'type' => 'unknown',
                'value' => 'unknown',
            ],
        }, $inputArguments);
    }
}
