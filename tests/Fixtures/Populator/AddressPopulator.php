<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Fixtures\Populator;

use Neusta\ConverterBundle\Converter\DefaultConverterContext;
use Neusta\ConverterBundle\Populator\Populator;
use Neusta\ConverterBundle\Tests\Fixtures\Model\PersonAddress;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Address;

/**
 * @implements Populator<Address, PersonAddress, DefaultConverterContext>
 */
class AddressPopulator implements Populator
{
    public function populate(object $target, object $source, ?object $ctx = null): void
    {
        $target->setStreet($source->getStreet());
        $target->setStreetNo($source->getStreetNo());
        $target->setPostalCode($source->getPostalCode());
        $target->setCity($source->getCity());
    }
}
