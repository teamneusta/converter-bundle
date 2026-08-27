<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\DependencyInjection\Compiler;

use Neusta\ConverterBundle\Converter;
use Neusta\ConverterBundle\Debug\Model\DebugInfo;
use Neusta\ConverterBundle\Debug\Model\ServiceArgumentInfo;
use Neusta\ConverterBundle\Debug\Model\ServiceInfo;
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

        // Maps the id of a decorated service to the id of its decorator, so that a decorated
        // service (e.g. a converter wrapped by ConverterWithDefaultContext) is reported under its
        // original, friendly id with the decorator's (i.e. the actually active) behavior - instead
        // of as two separate, confusing entries. This runs before Symfony's DecoratorServicePass,
        // so getDecoratedService() is still populated on decorator definitions at this point.
        $decoratorIds = [];
        foreach ($container->getDefinitions() as $id => $definition) {
            if ($decoratedService = $definition->getDecoratedService()) {
                $decoratorIds[$decoratedService[0]] = $id;
            }
        }

        foreach ($container->getDefinitions() as $id => $definition) {
            if ($definition->getDecoratedService()) {
                continue;
            }

            $resolvedId = $id;
            while (isset($decoratorIds[$resolvedId])) {
                $definition = $container->getDefinition($resolvedId = $decoratorIds[$resolvedId]);
            }

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
                $serviceInfo = $this->getServiceInfo($type, $definition, $reflection);
                $debugInfo->addMethodCall('add', [$id, $serviceInfo]);
            }
        }
    }

    private function getClassReflection(ContainerBuilder $container, Definition $definition): ?\ReflectionClass
    {
        while ((null === $class = $definition->getClass()) && $definition instanceof ChildDefinition) {
            // The parent may be missing entirely (e.g. a service provided by a bundle that is not
            // registered), in which case there is nothing to resolve the class from.
            if (!($parentId = $definition->getParent()) || !$container->has($parentId)) {
                break;
            }

            $definition = $container->findDefinition($parentId);
        }

        if (null === $class) {
            return null;
        }

        return $container->getReflectionClass($class, false);
    }

    private function getServiceInfo(string $type, Definition $definition, \ReflectionClass $classReflection): Definition
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
            ->setArguments([$type, $classReflection->name, $argumentsInfo]);
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
                // Inlined definitions (e.g. the mappers of a converting populator) carry the
                // references that make up the service graph, so they must be traversed.
                $argument instanceof Definition => ['array', $this->getArgumentInfo(
                    ['class' => $argument->getClass()] + $argument->getArguments(),
                )],
                \is_object($argument) => ['object', 'object(' . $argument::class . ')'],
                default => ['unknown', get_debug_type($argument)],
            }),
            $arguments,
        );
    }
}
