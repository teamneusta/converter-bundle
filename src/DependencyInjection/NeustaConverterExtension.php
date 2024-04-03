<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\DependencyInjection;

use Neusta\ConverterBundle\Converter;
use Neusta\ConverterBundle\DependencyInjection\Converter\ConverterFactory;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Neusta\ConverterBundle\Populator\PropertyMappingPopulator;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
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
     * @param array<string, mixed> $config
     */
    public function loadInternal(array $config, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(dirname(__DIR__, 2) . '/config'));
        $loader->load('services.yaml');

        foreach ($config['converters'] as $name => $converter) {
            $this->createConverter($container, $name, $converter);
        }

        foreach ($config['converter'] as $name => $converter) {
            $this->createDeprecatedConverter($container, $name, $converter);
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
        foreach ($config['properties'] ?? [] as $targetProperty => $sourceProperty) {
            $config['populators'][] = $propertyPopulatorId = "{$id}.populator.{$targetProperty}";
            $container->register($propertyPopulatorId, PropertyMappingPopulator::class)
                ->setArguments([
                    '$targetProperty' => $targetProperty,
                    '$sourceProperty' => $sourceProperty ?? $targetProperty,
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

    private function ensureSuffix(string $value, string $suffix): string
    {
        return str_ends_with($value, $suffix) ? $value : $value . $suffix;
    }
}
