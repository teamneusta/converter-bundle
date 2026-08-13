<?php
declare(strict_types=1);

namespace Neusta\ConverterBundle\DependencyInjection\Compiler;

use Neusta\ConverterBundle\DependencyInjection\DefinitionReflector;
use Neusta\ConverterBundle\Populator;
use Neusta\ConverterBundle\Populator\CustomContract\ParameterOrder;
use Neusta\ConverterBundle\Populator\CustomContract\PopulatorContract;
use Neusta\ConverterBundle\Populator\CustomContractPopulator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Wraps populators that implement a custom contract instead of {@see Populator}
 * so that converters can call them through the regular {@see Populator} interface.
 *
 * Must run *after* {@see DebugInfoPass}, which relies on seeing the original
 * populator services rather than the wrappers registered here.
 */
final class CustomContractPopulatorPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        foreach ($container->findTaggedServiceIds('neusta_converter.converter') as $id => $tags) {
            $converter = $container->getDefinition($id);

            if (!\array_key_exists('$populators', $converter->getArguments())) {
                throw new InvalidArgumentException(\sprintf(
                    'Service "%s" is tagged as "neusta_converter.converter" but has no "$populators" argument. '
                    . 'Custom populator contracts can only be resolved for converters registered by this bundle.',
                    $id,
                ));
            }

            /** @var Reference[] $populatorRefs */
            $populatorRefs = $converter->getArgument('$populators');

            $converter->setArgument('$populators', array_map(
                fn (Reference $populatorRef) => $this->resolvePopulator($container, (string) $populatorRef),
                $populatorRefs,
            ));
        }
    }

    private function resolvePopulator(ContainerBuilder $container, string $populatorId): Reference
    {
        $definition = $container->findDefinition($populatorId);

        if (!$reflection = DefinitionReflector::reflect($container, $definition)) {
            throw new InvalidArgumentException(\sprintf(
                'Class "%s" used for service "%s" cannot be found.',
                $definition->getClass() ?? '',
                $populatorId,
            ));
        }

        if ($reflection->implementsInterface(Populator::class)) {
            return new Reference($populatorId);
        }

        $contract = PopulatorContract::fromReflection($reflection);
        $wrapperId = "{$populatorId}.populator";

        if (!$container->hasDefinition($wrapperId)) {
            $container->setDefinition($wrapperId, (new Definition(CustomContractPopulator::class))->setArguments([
                '$populator' => (new Definition(\Closure::class))
                    ->setFactory([\Closure::class, 'fromCallable'])
                    ->addArgument([new Reference($populatorId), $contract->methodName]),
                '$parameterOrder' => (new Definition(ParameterOrder::class))
                    ->setFactory([ParameterOrder::class, 'fromArray'])
                    ->addArgument($contract->parameterOrder->toArray()),
            ]));
        }

        return new Reference($wrapperId);
    }
}
