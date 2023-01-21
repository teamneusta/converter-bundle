<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\DependencyInjection;

use Neusta\ConverterBundle\Converter;
use Neusta\ConverterBundle\Populator\ArrayConvertingPopulator;
use Neusta\ConverterBundle\Populator\ContextMappingPopulator;
use Neusta\ConverterBundle\Populator\ConvertingPopulator;
use Neusta\ConverterBundle\Populator\PropertyMappingPopulator;
use Neusta\ConverterBundle\TargetFactory\GenericTargetFactory;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\TypedReference;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

final class NeustaConverterExtension extends ConfigurableExtension
{
    /**
     * @param array<string, mixed> $mergedConfig
     */
    public function loadInternal(array $mergedConfig, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(\dirname(__DIR__, 2) . '/config'));
        $loader->load('services.yaml');

        foreach ($mergedConfig['converter'] as $converterId => $converter) {
            $this->registerConverterConfiguration($converterId, $converter, $container);
        }

        foreach ($mergedConfig['populator'] as $populatorId => $populator) {
            $this->registerPopulatorConfiguration($populatorId, $populator, $container);
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    private function registerConverterConfiguration(string $id, array $config, ContainerBuilder $container): void
    {
        if (!$targetFactoryId = $config['target_factory'] ?? null) {
            $container->register($targetFactoryId = "{$id}.target_factory", GenericTargetFactory::class)
                ->setArguments([
                    '$type' => new Reference($config['target']),
                ]);
        }

        foreach ($config['properties'] ?? [] as $targetProperty => $sourceConfig) {
            $skipNull = false;
            if (str_ends_with($targetProperty, '?')) {
                $skipNull = true;
                $targetProperty = substr($targetProperty, 0, -1);
            }
            $config['populators'][] = $propertyPopulatorId = "{$id}.populator.{$targetProperty}";
            $container->register($propertyPopulatorId, PropertyMappingPopulator::class)
                ->setArguments([
                    '$targetProperty' => $targetProperty,
                    '$sourceProperty' => $sourceConfig['source'] ?? $targetProperty,
                    '$defaultValue' => $sourceConfig['default'] ?? null,
                    '$mapper' => null,
                    '$accessor' => new Reference('property_accessor'),
                    '$skipNull' => $sourceConfig['skip_null'] || $skipNull,
                ]);
        }

        foreach ($config['context'] ?? [] as $targetProperty => $contextProperty) {
            $config['populators'][] = $contextPopulatorId = "{$id}.populator.context.{$targetProperty}";
            $container->register($contextPopulatorId, ContextMappingPopulator::class)
                ->setArguments([
                    '$targetProperty' => $targetProperty,
                    '$contextProperty' => $contextProperty ?? $targetProperty,
                    '$mapper' => null,
                    '$accessor' => new Reference('property_accessor'),
                ]);
        }

        $container->registerAliasForArgument($id, Converter::class, $this->appendSuffix($id, 'Converter'));
        $container->register($id, $config['converter'])
            ->setPublic(true)
            ->setArguments([
                '$factory' => new Reference($targetFactoryId),
                '$populators' => array_map(
                    static fn (string $populator) => new Reference($populator),
                    $config['populators'],
                ),
            ]);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function registerPopulatorConfiguration(string $id, array $config, ContainerBuilder $container): void
    {
        $targetProperty = array_key_first($config['property']);
        $sourceProperty = $config['property'][$targetProperty];

        $container->register($id, $config['populator'])
            ->setPublic(true)
            ->setArguments(match ($config['populator']) {
                ConvertingPopulator::class => [
                    '$converter' => new TypedReference($config['converter'], Converter::class),
                    '$targetPropertyName' => $targetProperty,
                    '$sourcePropertyName' => $sourceProperty['source'] ?? $targetProperty,
                    '$accessor' => new Reference('property_accessor'),
                ],
                ArrayConvertingPopulator::class => [
                    '$converter' => new TypedReference($config['converter'], Converter::class),
                    '$targetPropertyName' => $targetProperty,
                    '$sourceArrayPropertyName' => $sourceProperty['source'] ?? $targetProperty,
                    '$sourceArrayItemPropertyName' => $sourceProperty['source_array_item'] ?? null,
                    '$accessor' => new Reference('property_accessor'),
                ],
                default => throw new InvalidConfigurationException(sprintf('The populator "%s" is not supported.', $config['populator'])),
            });
    }

    private function appendSuffix(string $value, string $suffix): string
    {
        return str_ends_with($value, $suffix) ? $value : $value . $suffix;
    }
}
