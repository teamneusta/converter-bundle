<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Dump;

use Neusta\ConverterBundle\Converter;
use Neusta\ConverterBundle\Populator;
use Neusta\ConverterBundle\TargetFactory;

class InspectedServicesRegistry
{
    private const KEY_CONVERTERS = 'converters';
    private const KEY_POPULATORS = 'populators';
    private const KEY_FACTORIES = 'factories';

    /** @var array<string, array<string, array<string, string|array<mixed>>>> */
    private array $services = [
        self::KEY_CONVERTERS => [],
        self::KEY_POPULATORS => [],
        self::KEY_FACTORIES => [],
    ];

    /**
     * @param class-string                           $class
     * @param array<int|string, string|array<mixed>> $arguments
     */
    public function add(string $id, string $class, array $arguments): void
    {
        try {
            $reflection = new \ReflectionClass($class);
            if ($reflection->implementsInterface(Converter::class)) {
                $this->services[self::KEY_CONVERTERS][$id] = [
                    'class' => $class,
                    'arguments' => $arguments,
                ];
            } elseif ($reflection->implementsInterface(Populator::class)) {
                $this->services[self::KEY_POPULATORS][$id] = [
                    'class' => $class,
                    'arguments' => $arguments,
                ];
            } elseif ($reflection->implementsInterface(TargetFactory::class)) {
                $this->services[self::KEY_FACTORIES][$id] = [
                    'class' => $class,
                    'arguments' => $arguments,
                ];
            }
        } catch (\ReflectionException) {
            // nothing to do
        }
    }

    /**
     * @return array<string, array<string, string|array<mixed>>>
     */
    public function allConverters(): array
    {
        return $this->services[self::KEY_CONVERTERS];
    }

    /**
     * @return array<string, array<string, string|array<mixed>>>
     */
    public function allFactories(): array
    {
        return $this->services[self::KEY_FACTORIES];
    }

    /**
     * @return array<string, array<string, string|array<mixed>>>
     */
    public function allPopulators(): array
    {
        return $this->services[self::KEY_POPULATORS];
    }
}
