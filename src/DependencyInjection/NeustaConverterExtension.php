<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\DependencyInjection;

use Neusta\ConverterBundle\Converter\Cache\InMemoryCache;
use Neusta\ConverterBundle\Converter\CachingConverter;
use Neusta\ConverterBundle\Converter;
use Neusta\ConverterBundle\Populator\PropertyMappingPopulator;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

final class NeustaConverterExtension extends ConfigurableExtension
{
    /**
     * @param array<string, mixed> $config
     */
    public function loadInternal(array $config, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(dirname(__DIR__, 2) . '/config'));
        $loader->load('services.yaml');

        foreach ($config['converter'] as $converterId => $converter) {
            $this->registerConverterConfiguration($converterId, $converter, $container);
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
                    '$accessor' => new Reference('property_accessor'),
                ]);
        }

        $container->registerAliasForArgument($id, Converter::class, $this->appendSuffix($id, 'Converter'));
        $container->register($id, $config['converter'])
            ->setPublic(true)
            ->setArguments([
                '$factory' => new Reference($config['target_factory']),
                '$populators' => array_map(
                    static fn (string $populator) => new Reference($populator),
                    $config['populators'],
                ),
            ]);

        if (isset($config['cache'])) {
            if (!$cacheId = $config['cache']['service'] ?? null) {
                $container->register($cacheId = "{$id}.cache", InMemoryCache::class)
                    ->setArguments([
                        '$keyFactory' => new Reference($config['cache']['key_factory']),
                    ]);
            }

            $container->register("{$id}.caching_converter", CachingConverter::class)
                ->setDecoratedService($id)
                ->setArguments([
                    '$inner' => new Reference('.inner'),
                    '$cache' => new Reference($cacheId),
                ]);
        }
    }

    private function appendSuffix(string $value, string $suffix): string
    {
        return str_ends_with($value, $suffix) ? $value : $value . $suffix;
    }
}
