<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\DependencyInjection;

use Neusta\ConverterBundle\CacheManagement\DefaultCacheManagement;
use Neusta\ConverterBundle\Converter\CachedConverter;
use Neusta\ConverterBundle\Converter\Converter;
use Neusta\ConverterBundle\Converter\DefaultConverter;
use Neusta\ConverterBundle\DependencyInjection\NeustaConverterExtension;
use Neusta\ConverterBundle\NeustaConverterBundle;
use Neusta\ConverterBundle\Tests\Fixtures\CacheManagement\UserKeyFactory;
use Neusta\ConverterBundle\Tests\Fixtures\Factory\PersonFactory;
use Neusta\ConverterBundle\Tests\Fixtures\Populator\PersonNamePopulator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Reference;

class NeustaConverterExtensionTest extends TestCase
{
    public function test_with_default_converter(): void
    {
        $container = $this->buildContainer([
            'converter' => [
                'foobar' => [
                    'target_factory' => PersonFactory::class,
                    'populators' => [
                        PersonNamePopulator::class,
                    ],
                ],
            ],
        ]);

        // converter
        $converter = $container->getDefinition('foobar');
        self::assertSame(DefaultConverter::class, $converter->getClass());
        self::assertTrue($converter->isPublic());
        self::assertTrue($container->hasAlias(Converter::class . ' $foobarConverter'));
        self::assertIsReference(PersonFactory::class, $converter->getArgument('$factory'));
        self::assertIsArray($converter->getArgument('$populators'));
        self::assertCount(1, $converter->getArgument('$populators'));
        self::assertIsReference(PersonNamePopulator::class, $converter->getArgument('$populators')[0]);
    }

    public function test_with_default_cached_converter(): void
    {
        $container = $this->buildContainer([
            'converter' => [
                'foobar' => [
                    'target_factory' => PersonFactory::class,
                    'populators' => [
                        PersonNamePopulator::class,
                    ],
                    'cached' => [
                        'key_factory' => UserKeyFactory::class,
                    ],
                ],
            ],
        ]);

        // converter
        $converter = $container->getDefinition('foobar');
        self::assertSame(DefaultConverter::class, $converter->getClass());
        self::assertTrue($converter->isPublic());
        self::assertTrue($container->hasAlias(Converter::class . ' $foobarConverter'));
        self::assertIsReference(PersonFactory::class, $converter->getArgument('$factory'));
        self::assertIsArray($converter->getArgument('$populators'));
        self::assertCount(1, $converter->getArgument('$populators'));
        self::assertIsReference(PersonNamePopulator::class, $converter->getArgument('$populators')[0]);

        // cached converter
        $cachedConverter = $container->getDefinition('foobar.cached');
        self::assertSame(CachedConverter::class, $cachedConverter->getClass());
        self::assertSame('foobar', $cachedConverter->getDecoratedService()[0]);
        self::assertIsReference('.inner', $cachedConverter->getArgument('$inner'));
        self::assertIsReference('foobar.cache_management', $cachedConverter->getArgument('$cacheManagement'));

        // cache management
        $cacheManagement = $container->getDefinition('foobar.cache_management');
        self::assertSame(DefaultCacheManagement::class, $cacheManagement->getClass());
        self::assertIsReference(UserKeyFactory::class, $cacheManagement->getArgument('$keyFactory'));
    }

    public function test_with_cache_with_service_and_key_factory_defined(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('You cannot use "service" and "key_factory" at the same time');

        $this->buildContainer([
            'converter' => [
                'foobar' => [
                    'target_factory' => PersonFactory::class,
                    'populators' => [
                        PersonNamePopulator::class,
                    ],
                    'cached' => [
                        'service' => DefaultCacheManagement::class,
                        'key_factory' => UserKeyFactory::class,
                    ],
                ],
            ],
        ]);
    }

    public function test_with_cache_without_service_and_key_factory_defined(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Either "service" or "key_factory" must be defined');

        $this->buildContainer([
            'converter' => [
                'foobar' => [
                    'target_factory' => PersonFactory::class,
                    'populators' => [
                        PersonNamePopulator::class,
                    ],
                    'cached' => [],
                ],
            ],
        ]);
    }

    private static function assertIsReference(string $expected, mixed $actual): void
    {
        self::assertInstanceOf(Reference::class, $actual);
        self::assertSame($expected, (string) $actual);
    }

    private function buildContainer(array $extensionConfig): ContainerBuilder
    {
        $container = new ContainerBuilder(new ParameterBag([
            'kernel.debug' => true,
            'kernel.bundles' => ['NeustaConverterBundle' => NeustaConverterBundle::class],
        ]));

        $container->registerExtension(new NeustaConverterExtension());
        $container->loadFromExtension('neusta_converter', $extensionConfig);

        $container->getCompilerPassConfig()->setOptimizationPasses([]);
        $container->getCompilerPassConfig()->setRemovingPasses([]);
        $container->getCompilerPassConfig()->setAfterRemovingPasses([]);

        $bundle = new NeustaConverterBundle();
        $bundle->build($container);

        $container->compile();

        return $container;
    }
}
