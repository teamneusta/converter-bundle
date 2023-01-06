<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Factory;

/**
 * @template T of object
 * @template C of object
 */
interface TargetTypeFactory
{
    /**
     * @param C|null $ctx
     *
     * @return T
     */
    public function create(?object $ctx = null): object;
}
