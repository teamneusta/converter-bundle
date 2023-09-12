<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\DependencyInjection;

use Neusta\ConverterBundle\Converter;
use Neusta\ConverterBundle\Converter\GenericConverter;
use Neusta\ConverterBundle\DependencyInjection\Handler\PopulatorConfigurationHandler;
use Neusta\ConverterBundle\DependencyInjection\NeustaConverterExtension;
use Neusta\ConverterBundle\NeustaConverterBundle;
use Neusta\ConverterBundle\Populator\ArrayConvertingPopulator;
use Neusta\ConverterBundle\Populator\ContextMappingPopulator;
use Neusta\ConverterBundle\Populator\ConvertingPopulator;
use Neusta\ConverterBundle\Populator\PropertyMappingPopulator;
use Neusta\ConverterBundle\Tests\Fixtures\Model\PersonFactory;
use Neusta\ConverterBundle\Tests\Fixtures\Populator\PersonNamePopulator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\TypedReference;

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
    public function test_with_mapped_context(): void
    {
        $container = $this->buildContainer([
            'converter' => [
                'foobar' => [
                    'target_factory' => PersonFactory::class,
                    'context' => [
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
        self::assertIsReference('foobar.populator.context.name', $converter->getArgument('$populators')[0]);
        self::assertIsReference('foobar.populator.context.ageInYears', $converter->getArgument('$populators')[1]);

        // name context populator
        $namePopulator = $container->getDefinition('foobar.populator.context.name');
        self::assertSame(ContextMappingPopulator::class, $namePopulator->getClass());
        self::assertIsReference('property_accessor', $namePopulator->getArgument('$accessor'));
        self::assertSame('name', $namePopulator->getArgument('$targetProperty'));
        self::assertSame('name', $namePopulator->getArgument('$contextProperty'));

        // ageInYears context populator
        $ageInYearsPopulator = $container->getDefinition('foobar.populator.context.ageInYears');
        self::assertSame(ContextMappingPopulator::class, $ageInYearsPopulator->getClass());
        self::assertIsReference('property_accessor', $ageInYearsPopulator->getArgument('$accessor'));
        self::assertSame('ageInYears', $ageInYearsPopulator->getArgument('$targetProperty'));
        self::assertSame('age', $ageInYearsPopulator->getArgument('$contextProperty'));
    }

    public function test_with_converting_populator(): void
    {
        $container = $this->buildContainer([
            'populators' => [
                'foobar' => [
                    'converter' => GenericConverter::class,
                    'property' => [
                        'targetTest' => 'sourceTest'
                    ],
                ],
            ],
        ]);

        // converter
        $populator = $container->getDefinition('foobar');

        self::assertSame(ConvertingPopulator::class, $populator->getClass());
        self::assertTrue($populator->isPublic());
        self::assertInstanceOf(TypedReference::class, $populator->getArgument('$converter'));
        self::assertSame('targetTest', $populator->getArgument('$targetPropertyName'));
        self::assertSame('sourceTest', $populator->getArgument('$sourcePropertyName'));
    }

    public function test_with_array_converting_populator(): void
    {
        $container = $this->buildContainer([
            'populators' => [
                'foobar' => [
                    'converter' => GenericConverter::class,
                    'property' => [
                        'targetTest' => 'sourceTest',
                    ],
                ],
            ],
        ]);

        // converter
        $populator = $container->getDefinition('foobar');

        self::assertSame(ConvertingPopulator::class, $populator->getClass());
        self::assertTrue($populator->isPublic());
        self::assertInstanceOf(TypedReference::class, $populator->getArgument('$converter'));
        self::assertSame('targetTest', $populator->getArgument('$targetPropertyName'));
        self::assertSame('sourceTest', $populator->getArgument('$sourcePropertyName'));
    }

    public function test_with_array_converting_populator_with_inner_property(): void
    {
        $container = $this->buildContainer([
            'populators' => [
                'foobar' => [
                    'class' => ArrayConvertingPopulator::class,
                    'converter' => GenericConverter::class,
                    'property' => [
                        'targetTest' => 'sourceTest',
                        'itemProperty' => 'value',
                    ],
                ],
            ],
        ]);

        // converter
        $populator = $container->getDefinition('foobar');

        self::assertSame(ArrayConvertingPopulator::class, $populator->getClass());
        self::assertSame('value', $populator->getArgument('$sourceArrayItemPropertyName'));
    }

    public function test_with_array_converting_populator_with_inner_property_same_name(): void
    {
        $container = $this->buildContainer([
            'populators' => [
                'foobar' => [
                    'class' => ArrayConvertingPopulator::class,
                    'converter' => GenericConverter::class,
                    'property' => [
                        'test' => null, // in yaml one will write ~
                        'itemProperty' => 'value',
                    ],
                ],
            ],
        ]);

        // converter
        $populator = $container->getDefinition('foobar');

        self::assertSame(ArrayConvertingPopulator::class, $populator->getClass());
        self::assertSame('test', $populator->getArgument('$targetPropertyName'));
        self::assertSame('test', $populator->getArgument('$sourcePropertyName'));
        self::assertSame('value', $populator->getArgument('$sourceArrayItemPropertyName'));
    }

    public function test_with_array_converting_populator_with_inner_property_first(): void
    {
        $container = $this->buildContainer([
            'populators' => [
                'foobar' => [
                    'class' => ArrayConvertingPopulator::class,
                    'converter' => GenericConverter::class,
                    'property' => [
                        'itemProperty' => 'value',
                        'targetTest' => 'sourceTest',
                    ],
                ],
            ],
        ]);

        // converter
        $populator = $container->getDefinition('foobar');

        self::assertSame(ArrayConvertingPopulator::class, $populator->getClass());
        self::assertSame('targetTest', $populator->getArgument('$targetPropertyName'));
        self::assertSame('sourceTest', $populator->getArgument('$sourcePropertyName'));
        self::assertSame('value', $populator->getArgument('$sourceArrayItemPropertyName'));
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
