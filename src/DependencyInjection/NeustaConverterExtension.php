<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\DependencyInjection;

use Neusta\ConverterBundle\CacheManagement\DefaultCacheManagement;
use Neusta\ConverterBundle\Converter\CachedConverter;
use Neusta\ConverterBundle\Converter\Converter;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

final class NeustaConverterExtension extends ConfigurableExtension
{
    /**
     * @param array<mixed> $config
     */
    public function loadInternal(array $config, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(dirname(__DIR__, 2) . '/config'));
        $loader->load('services.yaml');

        $this->registerConverterConfiguration($config['converter'], $container);
    }

    private function registerConverterConfiguration(array $config, ContainerBuilder $container): void
    {
        foreach ($config as $converterId => $converter) {
            $container->registerAliasForArgument($converterId, Converter::class);
            $container->register($converterId, $converter['converter'])
                ->setPublic(true)
                ->setArguments([
                    '$factory' => new Reference($converter['target_factory']),
                    '$populators' => array_map(
                        static fn (string $populator) => new Reference($populator),
                        $converter['populators'],
                    ),
                ]);

            if (isset($config['cached'])) {
                $cacheManagementId = "{$converterId}.cache_management";
                if ($config['cached']['service']) {
                    $container->setAlias($cacheManagementId, $config['cached']['service']);
                } else {
                    $container->register($cacheManagementId, DefaultCacheManagement::class)
                        ->setArguments([
                            '$keyFactory' => new Reference($config['cached']['key_factory']),
                        ]);
                }

                $container->register("{$converterId}.cached", CachedConverter::class)
                    ->setDecoratedService($converterId)
                    ->setArguments([
                        '$inner' => new Reference('.inner'),
                        '$cacheManagement' => new Reference($cacheManagementId),
                    ]);
            }
        }
    }
}
