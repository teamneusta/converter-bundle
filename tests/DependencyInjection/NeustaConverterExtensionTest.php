<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\DependencyInjection;

use Neusta\ConverterBundle\Converter;
use Neusta\ConverterBundle\Converter\GenericConverter;
use Neusta\ConverterBundle\DependencyInjection\NeustaConverterExtension;
use Neusta\ConverterBundle\NeustaConverterBundle;
use Neusta\ConverterBundle\Populator\PropertyMappingPopulator;
use Neusta\ConverterBundle\Tests\Fixtures\Model\PersonFactory;
use Neusta\ConverterBundle\Tests\Fixtures\Populator\PersonNamePopulator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Reference;

class NeustaConverterExtensionTest extends TestCase
{
    public function test_with_generic_converter(): void
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
        self::assertSame(GenericConverter::class, $converter->getClass());
        self::assertTrue($converter->isPublic());
        self::assertTrue($container->hasAlias(Converter::class . ' $foobarConverter'));
        self::assertIsReference(PersonFactory::class, $converter->getArgument('$factory'));
        self::assertIsArray($converter->getArgument('$populators'));
        self::assertCount(1, $converter->getArgument('$populators'));
        self::assertIsReference(PersonNamePopulator::class, $converter->getArgument('$populators')[0]);
    }

    public function test_with_mapped_properties(): void
    {
        $container = $this->buildContainer([
            'converter' => [
                'foobar' => [
                    'target_factory' => PersonFactory::class,
                    'properties' => [
                        'name' => null,
                        'ageInYears' => 'age',
                    ],
                ],
            ],
        ]);

        // converter
        $converter = $container->getDefinition('foobar');
        self::assertSame(GenericConverter::class, $converter->getClass());
        self::assertTrue($converter->isPublic());
        self::assertTrue($container->hasAlias(Converter::class . ' $foobarConverter'));
        self::assertIsReference(PersonFactory::class, $converter->getArgument('$factory'));
        self::assertIsArray($converter->getArgument('$populators'));
        self::assertCount(2, $converter->getArgument('$populators'));
        self::assertIsReference('foobar.populator.name', $converter->getArgument('$populators')[0]);
        self::assertIsReference('foobar.populator.ageInYears', $converter->getArgument('$populators')[1]);

        // name property populator
        $namePopulator = $container->getDefinition('foobar.populator.name');
        self::assertSame(PropertyMappingPopulator::class, $namePopulator->getClass());
        self::assertIsReference('property_accessor', $namePopulator->getArgument('$accessor'));
        self::assertSame('name', $namePopulator->getArgument('$targetProperty'));
        self::assertSame('name', $namePopulator->getArgument('$sourceProperty'));

        // ageInYears property populator
        $ageInYearsPopulator = $container->getDefinition('foobar.populator.ageInYears');
        self::assertSame(PropertyMappingPopulator::class, $ageInYearsPopulator->getClass());
        self::assertIsReference('property_accessor', $ageInYearsPopulator->getArgument('$accessor'));
        self::assertSame('ageInYears', $ageInYearsPopulator->getArgument('$targetProperty'));
        self::assertSame('age', $ageInYearsPopulator->getArgument('$sourceProperty'));
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
