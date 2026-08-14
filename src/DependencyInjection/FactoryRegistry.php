<?php
declare(strict_types=1);

namespace Neusta\ConverterBundle\DependencyInjection;

use Neusta\ConverterBundle\DependencyInjection\Converter\ConverterFactory;
use Neusta\ConverterBundle\DependencyInjection\Populator\PopulatorFactory;
use Neusta\ConverterBundle\DependencyInjection\Populator\PropertyMappingPopulatorFactory;
use Neusta\ConverterBundle\DependencyInjection\Populator\PropertyPopulatorFactory;

final class FactoryRegistry
{
    /** @var array<string, ConverterFactory> */
    private array $converterFactories = [];
    /** @var array<string, PopulatorFactory> */
    private array $populatorFactories = [];

    /**
     * @param list<ConverterFactory> $converterFactories
     * @param list<PopulatorFactory> $populatorFactories
     */
    public function __construct(array $converterFactories, array $populatorFactories)
    {
        foreach ($converterFactories as $factory) {
            $this->addConverterFactory($factory);
        }

        foreach ($populatorFactories as $factory) {
            $this->addPopulatorFactory($factory);
        }
    }

    public function addConverterFactory(ConverterFactory $factory): void
    {
        if ($this->isNewFactory($this->converterFactories, $factory, 'converter')) {
            $this->converterFactories[$factory->getType()] = $factory;
        }
    }

    public function addPopulatorFactory(PopulatorFactory $factory): void
    {
        if ($this->isNewFactory($this->populatorFactories, $factory, 'populator')) {
            $this->populatorFactories[$factory->getType()] = $factory;
        }
    }

    /**
     * Registering the very same factory twice is not an error: the kernel builds the container more
     * than once (e.g. for the config builder cache warmer), which runs every bundle's `build()`
     * again on the same extension instance. Two *different* factories for one type are an error.
     *
     * @param array<string, ConverterFactory|PopulatorFactory> $factories
     */
    private function isNewFactory(array $factories, ConverterFactory|PopulatorFactory $factory, string $kind): bool
    {
        $registered = $factories[$factory->getType()] ?? null;

        if (null === $registered) {
            return true;
        }

        if ($registered::class === $factory::class) {
            return false;
        }

        throw new \InvalidArgumentException(\sprintf(
            'There is already a %s factory of type "%s" registered ("%s"), so "%s" cannot be registered as well.',
            $kind,
            $factory->getType(),
            $registered::class,
            $factory::class,
        ));
    }

    /**
     * @return array<string, ConverterFactory>
     */
    public function getConverterFactories(): array
    {
        return $this->converterFactories;
    }

    public function getConverterFactory(string $type): ?ConverterFactory
    {
        return $this->converterFactories[$type] ?? null;
    }

    /**
     * @return array<string, PopulatorFactory>
     */
    public function getPopulatorFactories(): array
    {
        return $this->populatorFactories;
    }

    public function getPopulatorFactory(string $type): ?PopulatorFactory
    {
        return $this->populatorFactories[$type] ?? null;
    }

    /**
     * The default populator type is what a property mapping without an explicit type key resolves
     * to, e.g. `properties: { fullName: name }`.
     */
    public function getDefaultPopulatorFactory(): PropertyMappingPopulatorFactory
    {
        $factory = $this->getPopulatorFactory(PropertyMappingPopulatorFactory::TYPE);

        if (!$factory instanceof PropertyMappingPopulatorFactory) {
            throw new \LogicException(\sprintf(
                'The mandatory populator factory for the default type "%s" is not registered. Expected an instance of "%s", got "%s".',
                PropertyMappingPopulatorFactory::TYPE,
                PropertyMappingPopulatorFactory::class,
                get_debug_type($factory),
            ));
        }

        return $factory;
    }

    /**
     * Resolves the populator factory for a single property mapping. The type is expressed as a
     * nested key (e.g. `converting`); without one the default type applies.
     *
     * @param array<string, mixed> $propertyConfig
     */
    public function getPropertyPopulatorFactoryFor(array $propertyConfig): PopulatorFactory
    {
        $types = array_keys(array_intersect_key($propertyConfig, $this->getPropertyPopulatorFactories()));

        if (\count($types) > 1) {
            throw new \LogicException(\sprintf(
                'Only one populator type can be set per property, got "%s".',
                implode('", "', $types),
            ));
        }

        return $this->populatorFactories[$types[0] ?? null] ?? $this->getDefaultPopulatorFactory();
    }

    /**
     * @return array<string, PropertyPopulatorFactory>
     */
    public function getPropertyPopulatorFactories(): array
    {
        return array_filter($this->populatorFactories, static fn ($factory) => $factory instanceof PropertyPopulatorFactory);
    }
}
