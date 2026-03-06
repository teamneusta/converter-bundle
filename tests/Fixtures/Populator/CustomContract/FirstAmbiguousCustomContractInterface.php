<?php
declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Fixtures\Populator\CustomContract;

use Neusta\ConverterBundle\Populator\CustomContract\Attribute\Source;
use Neusta\ConverterBundle\Populator\CustomContract\Attribute\Target;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Source\User;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Target\Person;

interface FirstAmbiguousCustomContractInterface
{
    public function populateFirst(
        #[Source] User $user,
        #[Target] Person $person,
    ): void;
}
