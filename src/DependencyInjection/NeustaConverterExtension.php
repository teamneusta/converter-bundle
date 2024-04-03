<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\DependencyInjection;

use Neusta\ConverterBundle\Converter;
use Neusta\ConverterBundle\DependencyInjection\Converter\ConverterFactory;
use Neusta\ConverterBundle\Populator\ArrayConvertingPopulator;
use Neusta\ConverterBundle\Populator\ContextMappingPopulator;
use Neusta\ConverterBundle\Populator\ConvertingPopulator;
use Neusta\ConverterBundle\Populator\PropertyMappingPopulator;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\TypedReference;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

final class NeustaConverterExtension extends ConfigurableExtension
{
    /** @var list<ConverterFactory> */
    private array $converterFactories = [];

    public function addConverterFactory(ConverterFactory $factory): void
    {
        $this->converterFactories[] = $factory;
    }

    /**
     * @param array<string, mixed> $config
     */
    public function getConfiguration(array $config, ContainerBuilder $container): Configuration
    {
        return new Configuration($this->converterFactories);
    }

    /**
     * @param array<string, mixed> $mergedConfig
     */
    public function loadInternal(array $mergedConfig, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(\dirname(__DIR__, 2) . '/config'));
        $loader->load('services.yaml');

        foreach ($mergedConfig['converters'] as $name => $converter) {
            $this->createConverter($container, $name, $converter);
        }

        foreach ($mergedConfig['converter'] as $name => $converter) {
            $this->createDeprecatedConverter($container, $name, $converter);
        }

        foreach ($mergedConfig['populator'] as $populatorId => $populator) {
            $this->registerPopulatorConfiguration($populatorId, $populator, $container);
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    private function createConverter(ContainerBuilder $container, string $name, array $config): void
    {
        foreach ($this->converterFactories as $factory) {
            if (!empty($config[$factory->getKey()])) {
                $factory->create($container, $name, $config[$factory->getKey()]);

                return;
            }
        }

        throw new InvalidConfigurationException(sprintf('Unable to create definition for "%s" converter.', $name));
    }

    /**
     * @param array<string, mixed> $config
     */
    public function createDeprecatedConverter(ContainerBuilder $container, string $id, array $config): void
    {
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

        $container->registerAliasForArgument($id, Converter::class, $this->ensureSuffix($id, 'Converter'));
        $container->register($id, $config['converter'])
            ->setPublic(true)
            ->setArguments([
                '$factory' => new Reference($config['target_factory']),
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

    private function ensureSuffix(string $value, string $suffix): string
    {
        return str_ends_with($value, $suffix) ? $value : $value . $suffix;
    }
}
