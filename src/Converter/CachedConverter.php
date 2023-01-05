<?php

namespace Neusta\ConverterBundle\Converter;

/**
 * @template S of object
 * @template T of object
 * @extends Converter<S, T>
 */
interface CachedConverter extends Converter
{
    // Marker Interface
}
