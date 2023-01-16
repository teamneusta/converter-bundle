<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Strategy;

/**
 * @template S of object
 * @template C of object
 */
interface ConverterSelector
{
    public function selectConverter(object $source, ?object $ctx = null): string;
}