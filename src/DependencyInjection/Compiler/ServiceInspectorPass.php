<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\DependencyInjection\Compiler;

use Neusta\ConverterBundle\Dump\InspectedServicesRegistry;
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
            $class = $definition->getClass() ?? 'unknown';

            $arguments = $this->handleArguments($definition->getArguments());

            $registry->addMethodCall('add', [$id, $class, $arguments]);
        }
    }

    /**
     * @param array<string|int, string|Reference|array<mixed>> $inputArguments
     *
     * @return array<string|int, string|Reference|array<mixed>>
     */
    private function handleArguments(array $inputArguments): array
    {
        $arguments = [];
        foreach ($inputArguments as $argumentName => $argumentValue) {
            if ($argumentValue instanceof Reference) {
                $arguments[$argumentName] = [
                    'value' => '@' . (string) $argumentValue,
                    'type' => 'reference',
                ];
            } elseif (\is_scalar($argumentValue)) {
                $arguments[$argumentName] = [
                    'value' => $argumentValue,
                    'type' => 'scalar',
                ];
            } elseif (\is_array($argumentValue)) {
                $arguments[$argumentName] = [
                    'value' => $this->handleArguments($argumentValue),
                    'type' => 'array',
                ];
            } elseif (\is_object($argumentValue)) {
                $arguments[$argumentName] = [
                    'value' => 'object(' . $argumentValue::class . ')',
                    'type' => 'object',
                ];
            } else {
                $arguments[$argumentName] = [
                    'value' => 'unknown',
                    'type' => 'unknown',
                ];
            }
        }

        return $arguments;
    }
}
