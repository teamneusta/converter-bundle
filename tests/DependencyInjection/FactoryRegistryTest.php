<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\DependencyInjection;

use Neusta\ConverterBundle\DependencyInjection\Converter\ConverterFactory;
use Neusta\ConverterBundle\DependencyInjection\Converter\GenericConverterFactory;
use Neusta\ConverterBundle\DependencyInjection\FactoryRegistry;
use Neusta\ConverterBundle\DependencyInjection\Populator\ArrayConvertingPopulatorFactory;
use Neusta\ConverterBundle\DependencyInjection\Populator\ConvertingPopulatorFactory;
use Neusta\ConverterBundle\DependencyInjection\Populator\PopulatorFactory;
use Neusta\ConverterBundle\DependencyInjection\Populator\PropertyMappingPopulatorFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class FactoryRegistryTest extends TestCase
{
    public function test_registers_factories_by_type(): void
    {
        $registry = new FactoryRegistry([new GenericConverterFactory()], [new ConvertingPopulatorFactory()]);

        self::assertInstanceOf(GenericConverterFactory::class, $registry->getConverterFactory('generic'));
        self::assertInstanceOf(ConvertingPopulatorFactory::class, $registry->getPopulatorFactory('converting'));
        self::assertNull($registry->getConverterFactory('unknown'));
        self::assertNull($registry->getPopulatorFactory('unknown'));
    }

    /**
     * The kernel builds the container more than once, so every bundle's `build()` - and with it
     * every factory registration - runs more than once on the same extension instance.
     */
    public function test_registering_the_same_factory_twice_is_a_no_op(): void
    {
        $registry = new FactoryRegistry([], []);

        $registry->addConverterFactory(new GenericConverterFactory());
        $registry->addConverterFactory(new GenericConverterFactory());
        $registry->addPopulatorFactory(new ConvertingPopulatorFactory());
        $registry->addPopulatorFactory(new ConvertingPopulatorFactory());

        self::assertCount(1, $registry->getConverterFactories());
        self::assertCount(1, $registry->getPopulatorFactories());
    }

    /**
     * "context_configurators" and "decorators" are siblings of the type key at
     * `neusta_converter.converters.<name>.*`, so a converter type can never be named after one of
     * them - otherwise a future bundle version adding a new reserved key could silently collide
     * with a type name a project already registered.
     */
    public function test_registering_a_converter_factory_with_a_reserved_type_fails(): void
    {
        $registry = new FactoryRegistry([], []);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The converter type "context_configurators" (registered by');

        $registry->addConverterFactory(new class implements ConverterFactory {
            public function getType(): string
            {
                return 'context_configurators';
            }

            public function addConfiguration(ArrayNodeDefinition $node, FactoryRegistry $factories): void
            {
            }

            public function create(ContainerBuilder $container, string $id, array $config, FactoryRegistry $factories): void
            {
            }
        });
    }

    /**
     * "decorators" is reserved for both converters and populators, even though it isn't implemented
     * for populators yet - "condition"/"target" are nested inside the type key today, but the
     * planned decorator mechanism (see docs/usage.md) will put "decorators" next to it, the same way
     * "context_configurators" already sits next to a converter's type key.
     */
    public function test_registering_a_populator_factory_with_a_reserved_type_fails(): void
    {
        $registry = new FactoryRegistry([], []);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The populator type "decorators" (registered by');

        $registry->addPopulatorFactory(new class implements PopulatorFactory {
            public function getType(): string
            {
                return 'decorators';
            }

            public function addConfiguration(ArrayNodeDefinition $node): void
            {
            }

            public function create(ContainerBuilder $container, string $id, array $config): void
            {
            }
        });
    }

    public function test_registering_a_conflicting_converter_factory_fails(): void
    {
        $registry = new FactoryRegistry([new GenericConverterFactory()], []);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('There is already a converter factory of type "generic" registered');

        $registry->addConverterFactory(new class implements ConverterFactory {
            public function getType(): string
            {
                return 'generic';
            }

            public function addConfiguration(ArrayNodeDefinition $node, FactoryRegistry $factories): void
            {
            }

            public function create(ContainerBuilder $container, string $id, array $config, FactoryRegistry $factories): void
            {
            }
        });
    }

    public function test_registering_a_conflicting_populator_factory_fails(): void
    {
        $registry = new FactoryRegistry([], [new ConvertingPopulatorFactory()]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('There is already a populator factory of type "converting" registered');

        $registry->addPopulatorFactory(new class implements PopulatorFactory {
            public function getType(): string
            {
                return 'converting';
            }

            public function addConfiguration(ArrayNodeDefinition $node): void
            {
            }

            public function create(ContainerBuilder $container, string $id, array $config): void
            {
            }
        });
    }

    public function test_default_populator_factory_is_mandatory(): void
    {
        $registry = new FactoryRegistry([], []);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The mandatory populator factory for the default type "property_mapping" is not registered.');

        $registry->getDefaultPopulatorFactory();
    }

    public function test_resolves_the_property_populator_factory_by_type_key(): void
    {
        $registry = new FactoryRegistry([], [
            new PropertyMappingPopulatorFactory(),
            new ConvertingPopulatorFactory(),
        ]);

        self::assertInstanceOf(
            ConvertingPopulatorFactory::class,
            $registry->getPropertyPopulatorFactoryFor(['source' => 'foo', 'converting' => ['converter' => 'x']]),
        );
        self::assertInstanceOf(
            PropertyMappingPopulatorFactory::class,
            $registry->getPropertyPopulatorFactoryFor(['source' => 'foo']),
        );
    }

    public function test_resolving_a_property_populator_factory_with_multiple_types_fails(): void
    {
        $registry = new FactoryRegistry([], [
            new PropertyMappingPopulatorFactory(),
            new ConvertingPopulatorFactory(),
            new ArrayConvertingPopulatorFactory(),
        ]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Only one populator type can be set per property, got "converting", "array_converting".');

        $registry->getPropertyPopulatorFactoryFor([
            'converting' => ['converter' => 'x'],
            'array_converting' => ['converter' => 'y'],
        ]);
    }
}
