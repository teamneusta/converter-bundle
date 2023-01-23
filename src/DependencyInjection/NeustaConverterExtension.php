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

        if (isset($config['cached'])) {
            $cacheManagementId = "{$id}.cache_management";
            if ($config['cached']['service']) {
                $container->setAlias($cacheManagementId, $config['cached']['service']);
            } else {
                $container->register($cacheManagementId, DefaultCacheManagement::class)
                    ->setArguments([
                        '$keyFactory' => new Reference($config['cached']['key_factory']),
                    ]);
            }

            $container->register("{$id}.cached", CachedConverter::class)
                ->setDecoratedService($id)
                ->setArguments([
                    '$inner' => new Reference('.inner'),
                    '$cacheManagement' => new Reference($cacheManagementId),
                ]);
        }
    }

    private function appendSuffix(string $value, string $suffix): string
    {
        return str_ends_with($value, $suffix) ? $value : $value . $suffix;
    }
}
