<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Fixtures\Factory;

use Neusta\ConverterBundle\Converter\ConverterContext;
use Neusta\ConverterBundle\Factory\TargetTypeFactory;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Person;

/**
 * @implements  TargetTypeFactory<Person>
 */
class PersonFactory implements TargetTypeFactory
{
    public function create(?ConverterContext $ctx = null): Person
    {
        return new Person();
    }
}
