<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Converter;

/**
 * @template S of object
 * @template T of object
 * @template C of object
 * @extends Converter<S, T, C>
 */
interface CachedConverter extends Converter
{
    // Marker Interface
}
