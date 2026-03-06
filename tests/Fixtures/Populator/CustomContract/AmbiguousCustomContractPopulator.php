<?php
declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Fixtures\Populator\CustomContract;

use Neusta\ConverterBundle\Tests\Fixtures\Model\Source\User;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Target\Person;

final class AmbiguousCustomContractPopulator implements FirstAmbiguousCustomContractInterface, SecondAmbiguousCustomContractInterface
{
    public function populateFirst(User $user, Person $person): void
    {
        $person->setFullName(implode(' ', [
            $user->getFirstname(),
            $user->getLastname(),
        ]));
    }

    public function populateSecond(User $user, Person $person): void
    {
        $person->setAge($user->getAgeInYears());
    }
}
