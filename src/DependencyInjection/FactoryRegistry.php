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
        $type = $factory->getType();

        if (isset($this->converterFactories[$type])) {
            throw new \InvalidArgumentException(sprintf('There is already a converter factory registered for the type "%s".', $type));
        }

        $this->converterFactories[$type] = $factory;
    }

    public function addPopulatorFactory(PopulatorFactory $factory): void
    {
        $type = $factory->getType();

        if (isset($this->populatorFactories[$type])) {
            throw new \InvalidArgumentException(sprintf('There is already a populator factory registered for the type "%s".', $type));
        }

        $this->populatorFactories[$type] = $factory;
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

    public function getPropertyMappingPopulatorFactory(): PropertyMappingPopulatorFactory
    {
        $factory = $this->getPopulatorFactory('property_mapping');
        \assert($factory instanceof PropertyMappingPopulatorFactory);

        return $factory;
    }

    /**
     * @param list<array-key> $types
     */
    public function getFirstMatchingPopulatorFactory(array $types): PopulatorFactory
    {
        foreach ($types as $type) {
            if (isset($this->populatorFactories[$type])) {
                return $this->populatorFactories[$type];
            }
        }

        return $this->getPropertyMappingPopulatorFactory();
    }

    /**
     * @return array<string, PropertyPopulatorFactory>
     */
    public function getPropertyPopulatorFactories(): array
    {
        return array_filter($this->populatorFactories, fn ($factory) => $factory instanceof PropertyPopulatorFactory);
    }
}
