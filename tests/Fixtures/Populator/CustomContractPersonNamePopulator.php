<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Fixtures\Populator;

use Neusta\ConverterBundle\Converter\Context\GenericContext;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Source\User;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Target\Person;

class CustomContractPersonNamePopulator implements CustomContractPersonPopulatorInterface
{
    public function populateName(User $user, Person $person, ?GenericContext $context = null): void
    {
        $separator = ' ';
        if ($context?->hasKey('separator')) {
            $separator = $context->getValue('separator');
        }

        $person->setFullName(implode(
            $separator,
            [
                $user->getFirstname(),
                $user->getLastname(),
            ]
        ));
    }
}
