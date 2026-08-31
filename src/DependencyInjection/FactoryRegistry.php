<?php
declare(strict_types=1);

namespace Neusta\ConverterBundle\DependencyInjection;

use Neusta\ConverterBundle\DependencyInjection\Converter\ConverterFactory;
use Neusta\ConverterBundle\DependencyInjection\Populator\PopulatorFactory;

/**
 * @experimental This class is not covered by the backward compatibility promise yet. Its shape
 *               will be settled once the first real consumers (see #39, #79) have exercised it -
 *               most likely by replacing the loose parameters with a factory context object (#108).
 */
final class FactoryRegistry
{
    /** Reserved: sibling keys of the converter type, see docs/usage.md. */
    private const RESERVED_CONVERTER_TYPES = ['context_configurators', 'decorators'];

    /** Reserved: sibling keys of the populator type, see docs/usage.md. */
    private const RESERVED_POPULATOR_TYPES = ['decorators'];

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
        $this->assertTypeIsNotReserved($factory, self::RESERVED_CONVERTER_TYPES, 'converter');

        if ($this->isNewFactory($this->converterFactories, $factory, 'converter')) {
            $this->converterFactories[$factory->getType()] = $factory;
        }
    }

    public function addPopulatorFactory(PopulatorFactory $factory): void
    {
        $this->assertTypeIsNotReserved($factory, self::RESERVED_POPULATOR_TYPES, 'populator');

        if ($this->isNewFactory($this->populatorFactories, $factory, 'populator')) {
            $this->populatorFactories[$factory->getType()] = $factory;
        }
    }

    /**
     * @param list<string> $reservedTypes
     */
    private function assertTypeIsNotReserved(ConverterFactory|PopulatorFactory $factory, array $reservedTypes, string $kind): void
    {
        if (\in_array($factory->getType(), $reservedTypes, true)) {
            throw new \InvalidArgumentException(\sprintf(
                'The %s type "%s" (registered by "%s") is reserved for bundle configuration and cannot be used as a type name.',
                $kind,
                $factory->getType(),
                $factory::class,
            ));
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
}
