<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Fixtures\Model;

use Neusta\ConverterBundle\Converter\Context\GenericContext;
use Neusta\ConverterBundle\TargetFactory;

/**
 * @implements TargetFactory<ContactNumber, GenericContext>
 */
class ContactNumberFactory implements TargetFactory
{
    public function create(object $ctx = null): ContactNumber
    {
        return new ContactNumber();
    }
}
