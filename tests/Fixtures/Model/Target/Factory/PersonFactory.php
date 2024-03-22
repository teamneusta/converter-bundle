<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Fixtures\Model\Target\Factory;

use Neusta\ConverterBundle\Converter\Context\GenericContext;
use Neusta\ConverterBundle\TargetFactory;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Target\Person;

/**
 * @implements TargetFactory<Person, GenericContext>
 */
class PersonFactory implements TargetFactory
{
    public function create(?object $ctx = null): Person
    {
        return new Person();
    }
}
