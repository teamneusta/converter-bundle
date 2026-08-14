<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\DependencyInjection;

use Neusta\ConverterBundle\Command\DebugCommand;
use Neusta\ConverterBundle\Converter;
use Neusta\ConverterBundle\Debug\Model\DebugInfo;
use Neusta\ConverterBundle\DependencyInjection\Converter\ConverterFactory;
use Neusta\ConverterBundle\DependencyInjection\Converter\GenericConverterFactory;
use Neusta\ConverterBundle\DependencyInjection\Populator\PopulatorFactory;
use Neusta\ConverterBundle\NeustaConverterBundle;
use Neusta\ConverterBundle\Populator\ArrayConvertingPopulator;
use Neusta\ConverterBundle\Populator\Condition\ExpressionCondition;
use Neusta\ConverterBundle\Populator\Condition\PropertyCondition;
use Neusta\ConverterBundle\Populator\ConditionalPopulator;
use Neusta\ConverterBundle\Populator\ConvertingPopulator;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Application;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\TypedReference;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

final class NeustaConverterExtension extends ConfigurableExtension
{
    public function __construct(
        private readonly FactoryRegistry $factories,
    ) {
    }

    public function getAlias(): string
    {
        return NeustaConverterBundle::ALIAS;
    }

    /**
     * Registers an additional converter type. Call this from another bundle's `build()` method:
     *
     *     public function build(ContainerBuilder $container): void
     *     {
     *         $extension = $container->getExtension(NeustaConverterBundle::ALIAS);
     *         \assert($extension instanceof NeustaConverterExtension);
     *
     *         $extension->addConverterFactory(new MyConverterFactory());
     *     }
     *
     * The kernel registers all extensions before it calls any `build()`, so this works regardless
     * of the order in which the bundles are registered.
     *
     * @experimental Not covered by the backward compatibility promise yet, see `ConverterFactory`.
     */
    public function addConverterFactory(ConverterFactory $factory): void
    {
        $this->factories->addConverterFactory($factory);
    }

    /**
     * Registers an additional populator type.
     *
     * @see self::addConverterFactory() for where to call this
     *
     * @experimental Not covered by the backward compatibility promise yet, see `PopulatorFactory`.
     */
    public function addPopulatorFactory(PopulatorFactory $factory): void
    {
        $this->factories->addPopulatorFactory($factory);
    }

    /**
     * @param array<string, mixed> $config
     */
    public function getConfiguration(array $config, ContainerBuilder $container): Configuration
    {
        return new Configuration($this->factories);
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

        if (!$container::willBeAvailable('symfony/console', Application::class, ['teamneusta/converter-bundle'])) {
            $container->removeDefinition(DebugCommand::class);
            $container->removeDefinition(DebugInfo::class);
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    private function createConverter(ContainerBuilder $container, string $id, array $config): void
    {
        $type = array_key_first($config) ?? 'unknown';
        $factory = $this->factories->getConverterFactory($type) ?? throw new InvalidConfigurationException(\sprintf(
            'Unable to create a definition for the converter "%s" because the type "%s" does not exist.',
            $id,
            $type,
        ));

        $factory->create($container, $id, $config[$type], $this->factories);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function createDeprecatedConverter(ContainerBuilder $container, string $id, array $config): void
    {
        $genericConverterFactory = $this->factories->getConverterFactory('generic');
        \assert($genericConverterFactory instanceof GenericConverterFactory);

        $genericConverterFactory->create($container, $id, $config, $this->factories);
        $container->getDefinition($id)->setClass($config['converter']);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function createPopulator(ContainerBuilder $container, string $id, array $config): void
    {
        $type = array_key_first($config) ?? 'unknown';
        $factory = $this->factories->getPopulatorFactory($type) ?? throw new InvalidConfigurationException(\sprintf(
            'Unable to create a definition for the populator "%s" because the type "%s" does not exist.',
            $id,
            $type,
        ));

        $factory->create($container, $id, $config[$type]);

        // A populator declared on its own is meant to be referenced and fetched, unlike the ones a
        // converter creates for its own `properties`/`context`, which stay private.
        $container->getDefinition($id)->setPublic(true);

        if (isset($config[$type]['condition'])) {
            $this->registerConditionalPopulatorConfiguration($id, $config[$type]['condition'], $container);
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    private function createDeprecatedPopulator(ContainerBuilder $container, string $id, array $config): void
    {
        $targetProperty = array_key_first($config['property']);
        $sourceProperty = $config['property'][$targetProperty];

        $container->register($id, $config['populator'])
            ->setPublic(true)
            ->setArguments(match ($config['populator']) {
                ConvertingPopulator::class => [
                    '$converter' => new TypedReference($config['converter'], Converter::class),
                    '$targetPropertyName' => $targetProperty,
                    '$sourcePropertyName' => $sourceProperty['source'] ?? $targetProperty,
                    '$accessor' => new Reference('property_accessor'),
                    '$skipNull' => $sourceProperty['skip_null'],
                ],
                ArrayConvertingPopulator::class => [
                    '$converter' => new TypedReference($config['converter'], Converter::class),
                    '$targetPropertyName' => $targetProperty,
                    '$sourceArrayPropertyName' => $sourceProperty['source'] ?? $targetProperty,
                    '$sourceArrayItemPropertyName' => $sourceProperty['source_array_item'] ?? null,
                    '$accessor' => new Reference('property_accessor'),
                ],
                default => throw new InvalidConfigurationException(\sprintf('The populator "%s" is not supported.', $config['populator'])),
            });

        if (isset($config['condition'])) {
            $this->registerConditionalPopulatorConfiguration($id, $config['condition'], $container);
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    private function registerConditionalPopulatorConfiguration(string $populatorId, array $config, ContainerBuilder $container): void
    {
        $conditionalPopulatorId = $populatorId . '.conditional';

        $condition = match (true) {
            isset($config['property']) => (new Definition(PropertyCondition::class))
                ->setArguments([
                    '$propertyName' => $config['property'],
                    '$propertyBase' => $config['property_base'],
                    '$expectedValue' => $config['expected_value'],
                    '$accessor' => new Reference('property_accessor'),
                ]),
            isset($config['expression']) => (new Definition(ExpressionCondition::class))
                ->setArguments([
                    '$expressionLanguage' => new Reference('neusta_converter.expression_language'),
                    '$expression' => $config['expression'],
                ]),
            default => throw new InvalidConfigurationException('The condition must be either a property or an expression.'),
        };

        $container->register($conditionalPopulatorId, ConditionalPopulator::class)
            ->setDecoratedService($populatorId)
            ->setArguments([
                '$populator' => new Reference($conditionalPopulatorId . '.inner'),
                '$condition' => (new Definition(\Closure::class))
                    ->setFactory([\Closure::class, 'fromCallable'])
                    ->addArgument([$condition, '__invoke']),
            ]);
    }
}
