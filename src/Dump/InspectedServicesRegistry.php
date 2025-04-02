<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Dump;

use Neusta\ConverterBundle\Converter;
use Neusta\ConverterBundle\Populator;
use Neusta\ConverterBundle\TargetFactory;

/**
 * @internal
 *
 * @phpstan-type ServiceType array{
 *     class: string,
 *     arguments: ServiceArgumentsType,
 * }
 * @phpstan-type ServiceArgumentsType array<int|string, array{
 *     type: string,
 *     value: scalar|array<mixed>,
 * }>
 */
final class InspectedServicesRegistry
{
    private const KEY_CONVERTERS = 'converters';
    private const KEY_POPULATORS = 'populators';
    private const KEY_FACTORIES = 'factories';

    /** @var array<string, array<string, ServiceType>> */
    private array $services = [
        self::KEY_CONVERTERS => [],
        self::KEY_POPULATORS => [],
        self::KEY_FACTORIES => [],
    ];

    /**
     * @param class-string  $class
     * @param ServiceArgumentsType $arguments
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
     * @return array<string, ServiceType>
     */
    public function allConverters(): array
    {
        return $this->services[self::KEY_CONVERTERS];
    }

    /**
     * @return array<string, ServiceType>
     */
    public function allFactories(): array
    {
        return $this->services[self::KEY_FACTORIES];
    }

    /**
     * @return array<string, ServiceType>
     */
    public function allPopulators(): array
    {
        return $this->services[self::KEY_POPULATORS];
    }
}
