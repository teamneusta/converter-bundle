<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\DependencyInjection\Compiler;

use Neusta\ConverterBundle\Dump\InspectedServicesRegistry;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ServiceInspectorPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(InspectedServicesRegistry::class)) {
            return;
        }

        $registry = $container->findDefinition(InspectedServicesRegistry::class);

        foreach ($container->getDefinitions() as $id => $definition) {
            $class = $definition instanceof ChildDefinition
                ? $container->findDefinition($definition->getParent())->getClass()
                : $definition->getClass();

            $arguments = $this->handleArguments($definition->getArguments());

            $registry->addMethodCall('add', [$id, $class ?? 'unknown', $arguments]);
        }
    }

    /**
     * @param array<string|int, string|Reference|array<mixed>> $inputArguments
     *
     * @return array<string|int, string|Reference|array<mixed>>
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
