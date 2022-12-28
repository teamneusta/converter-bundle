<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Populator;

use Neusta\ConverterBundle\Converter\ConverterContext;

/**
 * @template S of object
 * @template T of object
 */
interface Populator
{
    /**
     * @param T $target
     * @param S $source
     */
    public function populate(object $target, object $source, ?ConverterContext $ctx = null): void;
}
