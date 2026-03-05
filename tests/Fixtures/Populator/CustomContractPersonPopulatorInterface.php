<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Fixtures\Populator;

use Neusta\ConverterBundle\Converter\Context\GenericContext;
use Neusta\ConverterBundle\Populator\CustomContract\Attribute\Context;
use Neusta\ConverterBundle\Populator\CustomContract\Attribute\Source;
use Neusta\ConverterBundle\Populator\CustomContract\Attribute\Target;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Source\User;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Target\Person;

interface CustomContractPersonPopulatorInterface
{
    public function populateName(
        #[Source] User $user,
        #[Target] Person $person,
        #[Context] ?GenericContext $context = null,
    ): void;
}
