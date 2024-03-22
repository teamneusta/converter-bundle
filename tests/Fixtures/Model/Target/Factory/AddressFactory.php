<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Fixtures\Model\Target\Factory;

use Neusta\ConverterBundle\Converter\Context\GenericContext;
use Neusta\ConverterBundle\TargetFactory;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Target\PersonAddress;

/**
 * @implements TargetFactory<PersonAddress, GenericContext>
 */
class AddressFactory implements TargetFactory
{
    public function create(?object $ctx = null): PersonAddress
    {
        return new PersonAddress();
    }
}
