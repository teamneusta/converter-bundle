<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Fixtures\Populator\CustomContract;

use Neusta\ConverterBundle\Tests\Fixtures\Model\Source\User;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Target\Person;

final class InvalidCustomContractPopulator implements InvalidCustomContractInterface
{
    public function populateInvalid(User $user, Person $person): void
    {
        $person->setAge($user->getAgeInYears());
    }
}
