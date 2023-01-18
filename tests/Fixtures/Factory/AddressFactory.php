<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Fixtures\Factory;

use Neusta\ConverterBundle\Converter\DefaultConverterContext;
use Neusta\ConverterBundle\Factory\TargetTypeFactory;
use Neusta\ConverterBundle\Tests\Fixtures\Model\PersonAddress;

/**
 * @implements TargetTypeFactory<PersonAddress, DefaultConverterContext>
 */
class AddressFactory implements TargetTypeFactory
{
    public function create(?object $ctx = null): PersonAddress
    {
        return new PersonAddress();
    }
}
