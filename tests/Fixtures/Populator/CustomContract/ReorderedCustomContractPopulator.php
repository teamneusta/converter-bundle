<?php
declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Fixtures\Populator\CustomContract;

use Neusta\ConverterBundle\Converter\Context\GenericContext;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Source\User;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Target\Person;

final class ReorderedCustomContractPopulator implements ReorderedCustomContractInterface
{
    public function populateName(Person $person, ?GenericContext $context, User $user): void
    {
        $separator = $context?->hasKey('separator') ? $context->getValue('separator') : ' ';

        $person->setFullName(implode($separator, [
            $user->getFirstname(),
            $user->getLastname(),
        ]));
    }
}
