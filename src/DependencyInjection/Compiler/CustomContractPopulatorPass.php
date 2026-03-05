<?php
declare(strict_types=1);

namespace Neusta\ConverterBundle\DependencyInjection\Compiler;

use Neusta\ConverterBundle\Populator;
use Neusta\ConverterBundle\Populator\CustomContract\ParameterOrder;
use Neusta\ConverterBundle\Populator\CustomContract\ParameterOrderResolver;
use Neusta\ConverterBundle\Populator\CustomContract\PopulatorContractResolver;
use Neusta\ConverterBundle\Populator\CustomContractPopulator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class CustomContractPopulatorPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        foreach ($container->findTaggedServiceIds('neusta_converter.converter') as $id => $attr) {
            $converter = $container->getDefinition($id);
            /** @var Reference[] $populatorRefs */
            $populatorRefs = $converter->getArgument('$populators');

            $populators = [];
            foreach ($populatorRefs as $populatorRef) {
                $populatorClass = $container->findDefinition((string) $populatorRef)->getClass();
                $populatorReflection = $container->getReflectionClass($populatorClass);

                if ($populatorReflection->implementsInterface(Populator::class)) {
                    $populators[] = $populatorRef;

                    continue;
                }

                $methodReflection = PopulatorContractResolver::resolvePopulateMethod($populatorReflection);

                $populators[] = (new Definition(CustomContractPopulator::class))->setArguments([
                    '$populator' => (new Definition(\Closure::class))
                        ->setFactory([\Closure::class, 'fromCallable'])
                        ->addArgument([$populatorRef, $methodReflection->getName()]),
                    '$parameterOrder' => (new Definition(ParameterOrder::class))
                        ->setFactory([ParameterOrder::class, 'fromArray'])
                        ->addArgument(ParameterOrderResolver::resolve($methodReflection)->toArray()),
                ]);
            }

            $converter->setArgument('$populators', $populators);
        }
    }
}
