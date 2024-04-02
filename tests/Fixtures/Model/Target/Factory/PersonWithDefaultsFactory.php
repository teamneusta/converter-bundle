<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Fixtures\Model\Target\Factory;

use Neusta\ConverterBundle\Converter\Context\GenericContext;
use Neusta\ConverterBundle\TargetFactory;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Target\Person;

/**
 * @implements TargetFactory<Person, GenericContext>
 */
class PersonWithDefaultsFactory implements TargetFactory
{
    public function create(?object $ctx = null): Person
    {
        return (new Person())
            ->setMail('default@test.de')
            ->setAge(39);
    }
}
