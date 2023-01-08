<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Populator;

/**
 * @template S of object
 * @template T of object
 * @template C of object
 */
interface Populator
{
    /**
     * @param T $target
     * @param S $source
     * @param C|null $ctx
     */
    public function populate(object $target, object $source, ?object $ctx = null): void;
}
