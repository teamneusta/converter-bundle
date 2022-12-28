<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Factory;

use Neusta\ConverterBundle\Converter\ConverterContext;

/**
 * @template T of object
 */
interface TargetTypeFactory
{
    /**
     * @return T
     */
    public function create(?ConverterContext $ctx = null): object;
}
