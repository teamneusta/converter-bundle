<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Fixtures\Populator;

use Neusta\ConverterBundle\Converter\Context\GenericContext;
use Neusta\ConverterBundle\Populator;
use Neusta\ConverterBundle\Tests\Fixtures\Model\PersonAddress;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Address;

/**
 * @implements Populator<Address, PersonAddress, GenericContext>
 */
class AddressPopulator implements Populator
{
    public function populate(object $target, object $source, ?GenericContext $ctx = null): void
    {
        $target->setStreet($source->getStreet());
        $target->setStreetNo($source->getStreetNo());
        $target->setPostalCode($source->getPostalCode());
        $target->setCity($source->getCity());
    }
}
