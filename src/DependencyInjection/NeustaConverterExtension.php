<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\DependencyInjection;

use Neusta\ConverterBundle\Converter;
use Neusta\ConverterBundle\DependencyInjection\Handler\PopulatorConfigurationHandler;
use Neusta\ConverterBundle\Populator\ArrayConvertingPopulator;
use Neusta\ConverterBundle\Populator\ContextMappingPopulator;
use Neusta\ConverterBundle\Populator\ConvertingPopulator;
use Neusta\ConverterBundle\Populator\PropertyMappingPopulator;
use Symfony\Component\Config\Definition\Exception\Exception;
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
        $loader = new YamlFileLoader($container, new FileLocator(dirname(__DIR__, 2) . '/config'));
        $loader->load('services.yaml');

        foreach ($mergedConfig['converter'] as $converterId => $converter) {
            $this->registerConverterConfiguration($converterId, $converter, $container);
        }

        foreach ($mergedConfig['populators'] as $populatorId => $populator) {
            $this->registerPopulatorConfiguration($populatorId, $populator, $container);
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    private function registerConverterConfiguration(string $id, array $config, ContainerBuilder $container): void
    {
        foreach ($config['properties'] ?? [] as $targetProperty => $sourceProperty) {
            $config['populators'][] = $propertyPopulatorId = "{$id}.populator.{$targetProperty}";
            $container->register($propertyPopulatorId, PropertyMappingPopulator::class)
                ->setArguments([
                    '$targetProperty' => $targetProperty,
                    '$sourceProperty' => $sourceProperty ?? $targetProperty,
                    '$mapper' => null,
                    '$accessor' => new Reference('property_accessor'),
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
                '$factory' => new Reference($config['target_factory']),
                '$populators' => array_map(
                    static fn(string $populator) => new Reference($populator),
                    $config['populators'],
                ),
            ]);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function registerPopulatorConfiguration(string $id, array $config, ContainerBuilder $container): void
    {
        $arguments = [];
        if (empty($config['class']) || $config['class'] === ConvertingPopulator::class) {
            $arguments = $this->buildArgumentsForConvertingPopulator($config);
        } elseif ($config['class'] === ArrayConvertingPopulator::class) {
            $arguments = $this->buildArgumentsForArrayConvertingPopulator($config);
        }

        $container->register($id, $config['class'])
            ->setPublic(true)
            ->setArguments($arguments);
    }

    /**
     * @param array<string, mixed> $config
     * @return array<string, string>
     */
    private function buildArgumentsForConvertingPopulator(array $config): array
    {
        $targetProperty = array_key_first($config['property']);
        $sourceProperty = $config['property'][$targetProperty];
        $sourceProperty = $sourceProperty ?? $targetProperty;

        return
            [
                '$converter' => new TypedReference($config['converter'], Converter::class),
                '$sourcePropertyName' => $sourceProperty,
                '$targetPropertyName' => $targetProperty,
                '$accessor' => new Reference('property_accessor'),
            ];
    }

    /**
     * @param array<string, mixed> $config
     * @return array<string, string>
     */
    private function buildArgumentsForArrayConvertingPopulator(array $config): array
    {
        $innerPropertyArgument = [];
        $innerProperty = $config['property']['itemProperty'];
        $innerPropertyArgument['$sourceArrayItemPropertyName'] = $innerProperty;
        unset($config['property']['itemProperty']);

        $arguments = array_merge(
            $innerPropertyArgument,
            $this->buildArgumentsForConvertingPopulator($config),
        );
        return $arguments;
    }

    private function appendSuffix(string $value, string $suffix): string
    {
        return str_ends_with($value, $suffix) ? $value : $value . $suffix;
    }
}
