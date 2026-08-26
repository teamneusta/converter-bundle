<?php
declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Fixtures\Populator\CustomContract;

use Neusta\ConverterBundle\Converter\Context\GenericContext;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Source\User;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Target\Person;

final class NonNullableContextCustomContractPopulator implements NonNullableContextCustomContractInterface
{
    public function populateName(User $user, Person $person, GenericContext $context): void
    {
        $person->setFullName($user->getFirstname());
    }
}
