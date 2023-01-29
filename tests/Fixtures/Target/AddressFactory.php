<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Fixtures\Target;

use Neusta\ConverterBundle\Converter\Context\GenericContext;
use Neusta\ConverterBundle\Target\TargetFactory;
use Neusta\ConverterBundle\Tests\Fixtures\Model\PersonAddress;

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
