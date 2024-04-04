<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\DependencyInjection;

use Neusta\ConverterBundle\DependencyInjection\Converter\ConverterFactory;
use Neusta\ConverterBundle\DependencyInjection\Populator\PopulatorFactory;
use Neusta\ConverterBundle\NeustaConverterBundle;
use Neusta\ConverterBundle\Populator\ArrayConvertingPopulator;
use Neusta\ConverterBundle\Populator\ConvertingPopulator;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

final class NeustaConverterExtension extends ConfigurableExtension
{
    /** @var array<string, ConverterFactory> */
    private array $converterFactories = [];
    /** @var array<string, PopulatorFactory> */
    private array $populatorFactories = [];

    /**
     * @param list<ConverterFactory> $converterFactories
     * @param list<PopulatorFactory> $populatorFactories
     */
    public function __construct(array $converterFactories, array $populatorFactories)
    {
        foreach ($converterFactories as $factory) {
            $this->addConverterFactory($factory);
        }

        foreach ($populatorFactories as $factory) {
            $this->addPopulatorFactory($factory);
        }
    }

    public function getAlias(): string
    {
        return NeustaConverterBundle::ALIAS;
    }

    /**
     * @param array<string, mixed> $config
     */
    public function getConfiguration(array $config, ContainerBuilder $container): Configuration
    {
        return new Configuration($this->converterFactories, $this->populatorFactories);
    }

    public function addConverterFactory(ConverterFactory $factory): void
    {
        $type = $factory->getType();

        if (isset($this->converterFactories[$type])) {
            throw new \InvalidArgumentException(sprintf('There is already a converter factory registered for the type "%s".', $type));
        }

        $this->converterFactories[$type] = $factory;
    }

    public function addPopulatorFactory(PopulatorFactory $factory): void
    {
        $type = $factory->getType();

        if (isset($this->populatorFactories[$type])) {
            throw new \InvalidArgumentException(sprintf('There is already a populator factory registered for the type "%s".', $type));
        }

        $this->populatorFactories[$type] = $factory;
    }

    /**
     * @param array<string, mixed> $mergedConfig
     */
    public function loadInternal(array $mergedConfig, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(\dirname(__DIR__, 2) . '/config'));
        $loader->load('services.yaml');

        foreach ($mergedConfig['converters'] as $id => $converter) {
            $this->createConverter($container, $id, $converter);
        }

        foreach ($mergedConfig['converter'] as $id => $converter) {
            $this->createDeprecatedConverter($container, $id, $converter);
        }

        foreach ($mergedConfig['populators'] as $id => $populator) {
            $this->createPopulator($container, $id, $populator);
        }

        foreach ($mergedConfig['populator'] as $id => $populator) {
            $this->createDeprecatedPopulator($container, $id, $populator);
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    private function createConverter(ContainerBuilder $container, string $id, array $config): void
    {
        $type = array_key_first($config);
        $factory = $this->converterFactories[$type] ?? throw new InvalidConfigurationException(sprintf(
            'Unable to create a definition for the converter "%s" because the type "%s" does not exist.',
            $id,
            $type,
        ));

        $factory->create($container, $id, $config[$type]);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function createDeprecatedConverter(ContainerBuilder $container, string $id, array $config): void
    {
        $this->converterFactories['generic']->create($container, $id, $config);
        $container->getDefinition($id)->setClass($config['converter']);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function createPopulator(ContainerBuilder $container, string $id, array $config): void
    {
        $type = array_key_first($config);
        $factory = $this->populatorFactories[$type] ?? throw new InvalidConfigurationException(sprintf(
            'Unable to create a definition for the populator "%s" because the type "%s" does not exist.',
            $id,
            $type,
        ));

        $factory->create($container, $id, $config[$type]);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function createDeprecatedPopulator(ContainerBuilder $container, string $id, array $config): void
    {
        $type = match ($config['populator']) {
            ConvertingPopulator::class => 'converting',
            ArrayConvertingPopulator::class => 'array_converting',
            default => throw new InvalidConfigurationException(sprintf('The populator "%s" is not supported.', $config['populator'])),
        };

        $this->populatorFactories[$type]->create($container, $id, $config);
    }
}
