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
    /** @var array<string, ServiceType> */
    private array $converters = [];
    /** @var array<string, ServiceType> */
    private array $populators = [];
    /** @var array<string, ServiceType> */
    private array $factories = [];

    /**
     * @param class-string  $class
     * @param ServiceArgumentsType $arguments
     */
    public function add(string $id, string $class, array $arguments): void
    {
        try {
            $reflection = new \ReflectionClass($class);
            if ($reflection->implementsInterface(Converter::class)) {
                $this->converters[$id] = [
                    'class' => $class,
                    'arguments' => $arguments,
                ];
            } elseif ($reflection->implementsInterface(Populator::class)) {
                $this->populators[$id] = [
                    'class' => $class,
                    'arguments' => $arguments,
                ];
            } elseif ($reflection->implementsInterface(TargetFactory::class)) {
                $this->factories[$id] = [
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
        return $this->converters;
    }

    /**
     * @return array<string, ServiceType>
     */
    public function allFactories(): array
    {
        return $this->factories;
    }

    /**
     * @return array<string, ServiceType>
     */
    public function allPopulators(): array
    {
        return $this->populators;
    }
}
