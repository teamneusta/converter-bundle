<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Fixtures\Factory;

use Neusta\ConverterBundle\Converter\DefaultConverterContext;
use Neusta\ConverterBundle\Factory\TargetTypeFactory;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Person;

/**
 * @implements TargetTypeFactory<Person, DefaultConverterContext>
 */
class PersonFactory implements TargetTypeFactory
{
    public function create(?object $ctx = null): Person
    {
        return new Person();
    }
}
