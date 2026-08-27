<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\DependencyInjection\Populator;

use Neusta\ConverterBundle\Populator\ContextMappingPopulator;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class ContextMappingPopulatorFactory implements PopulatorFactory
{
    public const TYPE = 'context_mapping';

    public function getType(): string
    {
        return self::TYPE;
    }

    public function addConfiguration(ArrayNodeDefinition $node): void
    {
        $node
            ->beforeNormalization()
                ->ifNull()
                ->then(static fn () => ['property' => null])
            ->end()
            ->beforeNormalization()
                ->ifString()
                // A namespace separator is required to opt into the "class" shortcut, so that a plain
                // property name (e.g. "locale") is never misread as an unrelated, unnamespaced class
                // that happens to be loaded (e.g. ext-intl's global `Locale` class - PHP class name
                // lookups are case-insensitive). A class-string shortcut that doesn't resolve to an
                // existing class still fails loudly via the "class" node's validation below.
                ->then(static fn (string $v) => str_contains($v, '\\') ? ['class' => $v] : ['property' => $v])
            ->end()
            ->children()
                ->scalarNode('class')
                    ->info('Class of the context object to read "property" from')
                    ->validate()
                        ->ifTrue(static fn ($v) => null !== $v && !class_exists($v))
                        ->thenInvalid('The context object class %s does not exist.')
                    ->end()
                ->end()
                ->scalarNode('property')->end()
                ->booleanNode('required')
                    ->info('Whether to fail instead of silently skipping the mapping if the context value/object is missing')
                    ->defaultFalse()
                ->end()
            ->end()
        ;
    }

    public function create(ContainerBuilder $container, string $id, array $config): void
    {
        $contextClass = $config['class'] ?? null;
        $contextProperty = $config['property']
            ?? (null !== $contextClass ? self::inferContextProperty($contextClass) : null)
            ?? $config['target'];

        $container->register($id, ContextMappingPopulator::class)
            ->setArguments([
                '$targetProperty' => $config['target'],
                '$contextClass' => $contextClass,
                '$contextProperty' => $contextProperty,
                '$mapper' => null,
                '$accessor' => new Reference('property_accessor'),
                '$required' => $config['required'] ?? false,
            ]);
    }

    /**
     * @param class-string $class
     */
    private static function inferContextProperty(string $class): ?string
    {
        $properties = array_values(array_filter(
            (new \ReflectionClass($class))->getProperties(),
            static fn (\ReflectionProperty $property) => !$property->isStatic(),
        ));

        return 1 === \count($properties) ? $properties[0]->getName() : null;
    }
}
