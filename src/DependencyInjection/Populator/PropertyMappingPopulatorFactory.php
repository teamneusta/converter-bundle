<?php
declare(strict_types=1);

namespace Neusta\ConverterBundle\DependencyInjection\Populator;

use Neusta\ConverterBundle\Populator\PropertyMappingPopulator;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class PropertyMappingPopulatorFactory implements PopulatorFactory
{
    public const TYPE = 'property_mapping';

    public function getType(): string
    {
        return self::TYPE;
    }

    final public function addConfiguration(ArrayNodeDefinition $node): void
    {
        $node
            ->beforeNormalization()
                ->ifNull()
                ->then(static fn () => ['source' => null, 'default' => null, 'skip_null' => false])
            ->end()
            ->beforeNormalization()
                ->ifString()
                ->then(static fn (string $v) => ['source' => $v, 'default' => null, 'skip_null' => false])
            ->end()
            ->children()
                ->scalarNode('source')
                    ->defaultValue(null)
                ->end()
                ->scalarNode('default')
                    ->defaultValue(null)
                ->end()
                ->booleanNode('skip_null')
                    ->defaultFalse()
                ->end()
            ->end()
        ;

        if (!$this->supportsDefaultValue()) {
            $node
                ->validate()
                    ->ifTrue(static fn (array $c) => null !== ($c['default'] ?? null))
                    ->thenInvalid('The "default" option is not supported for this populator type.')
                ->end()
            ;
        }
    }

    final public function create(ContainerBuilder $container, string $id, array $config): void
    {
        $container->register($id, PropertyMappingPopulator::class)
            ->setArguments([
                '$targetProperty' => $config['target'],
                '$sourceProperty' => $config['source'] ?? $config['target'],
                '$defaultValue' => $config['default'],
                '$mapper' => $this->getMapperDefinition($config),
                '$accessor' => new Reference('property_accessor'),
                '$skipNull' => $config['skip_null'],
            ]);
    }

    /**
     * @param array<string, mixed> $config
     */
    protected function getMapperDefinition(array $config): ?Definition
    {
        return null;
    }

    /**
     * The default value is applied to the *source* value before the mapper runs. A mapper that does
     * not pass scalars through - `ArrayPropertyMapper` returns `[]` for anything non-array - would
     * silently discard it again, so those types reject the option instead.
     */
    protected function supportsDefaultValue(): bool
    {
        return true;
    }
}
