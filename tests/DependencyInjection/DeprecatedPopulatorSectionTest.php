<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\DependencyInjection;

use Neusta\ConverterBundle\Converter;
use Neusta\ConverterBundle\Converter\GenericConverter;
use Neusta\ConverterBundle\Populator\ArrayConvertingPopulator;
use Neusta\ConverterBundle\Populator\Condition\ExpressionCondition;
use Neusta\ConverterBundle\Populator\Condition\PropertyCondition;
use Neusta\ConverterBundle\Populator\ConditionalPopulator;
use Neusta\ConverterBundle\Populator\ConvertingPopulator;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\TypedReference;

/**
 * The deprecated `neusta_converter.populator` section is hand-rolled and still emits the legacy
 * `ConvertingPopulator`/`ArrayConvertingPopulator` classes instead of a `PropertyMappingPopulator`
 * with a mapper.
 *
 * @todo remove together with the deprecated config in 2.0
 */
class DeprecatedPopulatorSectionTest extends NeustaConverterExtensionTestCase
{
    public function test_with_converting_populator(): void
    {
        $this->load([
            'populator' => [
                'foobar' => [
                    'converter' => GenericConverter::class,
                    'property' => [
                        'targetTest' => 'sourceTest',
                    ],
                ],
            ],
        ]);

        $this->assertContainerBuilderHasPublicService('foobar', ConvertingPopulator::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$converter', new TypedReference(GenericConverter::class, Converter::class));
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$targetPropertyName', 'targetTest');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$sourcePropertyName', 'sourceTest');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$skipNull', false);
    }

    public function test_with_converting_populator_without_source_property_config(): void
    {
        $this->load([
            'populator' => [
                'foobar' => [
                    'converter' => GenericConverter::class,
                    'property' => [
                        'test' => null,
                    ],
                ],
            ],
        ]);

        $this->assertContainerBuilderHasPublicService('foobar', ConvertingPopulator::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$targetPropertyName', 'test');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$sourcePropertyName', 'test');
    }

    public function test_with_converting_populator_and_skip_null(): void
    {
        $this->load([
            'populator' => [
                'foobar' => [
                    'converter' => GenericConverter::class,
                    'property' => [
                        'targetTest' => [
                            'source' => 'sourceTest',
                            'skip_null' => true,
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$skipNull', true);
    }

    public function test_with_converting_populator_with_array_converting_populator_config(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The "property.<target>.source_array_item" option is only supported for array converting populators.');

        $this->load([
            'populator' => [
                'foobar' => [
                    'converter' => GenericConverter::class,
                    'property' => [
                        'test' => [
                            'source_array_item' => 'value',
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function test_with_array_converting_populator_with_inner_property(): void
    {
        $this->load([
            'populator' => [
                'foobar' => [
                    'populator' => ArrayConvertingPopulator::class,
                    'converter' => GenericConverter::class,
                    'property' => [
                        'targetTest' => [
                            'source' => 'sourceTest',
                            'source_array_item' => 'value',
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertContainerBuilderHasPublicService('foobar', ArrayConvertingPopulator::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$converter', new TypedReference(GenericConverter::class, Converter::class));
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$targetPropertyName', 'targetTest');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$sourceArrayPropertyName', 'sourceTest');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$sourceArrayItemPropertyName', 'value');
    }

    public function test_with_property_condition(): void
    {
        $this->load([
            'populator' => [
                'foobar' => [
                    'converter' => GenericConverter::class,
                    'property' => [
                        'targetTest' => 'sourceTest',
                    ],
                    'condition' => [
                        'property' => 'ageInYears',
                        'expected_value' => 18,
                    ],
                ],
            ],
        ]);

        $this->assertContainerBuilderHasService('foobar.conditional', ConditionalPopulator::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar.conditional', '$populator', new Reference('foobar.conditional.inner'));
        self::assertSame('foobar', $this->container->getDefinition('foobar.conditional')->getDecoratedService()[0]);

        $condition = $this->conditionDefinitionOf('foobar.conditional');
        self::assertSame(PropertyCondition::class, $condition->getClass());
        self::assertSame('ageInYears', $condition->getArgument('$propertyName'));
        self::assertSame('source', $condition->getArgument('$propertyBase'));
        self::assertSame(18, $condition->getArgument('$expectedValue'));
    }

    public function test_with_expression_condition(): void
    {
        $this->load([
            'populator' => [
                'foobar' => [
                    'converter' => GenericConverter::class,
                    'property' => [
                        'targetTest' => 'sourceTest',
                    ],
                    'condition' => [
                        'expression' => 'source.getAgeInYears() >= 18',
                    ],
                ],
            ],
        ]);

        $condition = $this->conditionDefinitionOf('foobar.conditional');
        self::assertSame(ExpressionCondition::class, $condition->getClass());
        self::assertSame('source.getAgeInYears() >= 18', $condition->getArgument('$expression'));
    }

    public function test_with_property_and_expression_condition(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('You can only define either "property" or "expression", not both.');

        $this->load([
            'populator' => [
                'foobar' => [
                    'converter' => GenericConverter::class,
                    'property' => [
                        'targetTest' => 'sourceTest',
                    ],
                    'condition' => [
                        'property' => 'ageInYears',
                        'expression' => 'source.getAgeInYears() >= 18',
                    ],
                ],
            ],
        ]);
    }

    /**
     * The condition is wrapped into a `Closure::fromCallable([$condition, '__invoke'])` definition.
     */
    private function conditionDefinitionOf(string $serviceId): \Symfony\Component\DependencyInjection\Definition
    {
        $closure = $this->container->getDefinition($serviceId)->getArgument('$condition');
        self::assertInstanceOf(\Symfony\Component\DependencyInjection\Definition::class, $closure);

        $callable = $closure->getArgument(0);
        self::assertIsArray($callable);
        self::assertInstanceOf(\Symfony\Component\DependencyInjection\Definition::class, $callable[0]);

        return $callable[0];
    }
}
