<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Fixtures\Populator;

use Neusta\ConverterBundle\Converter\Context\ContextInterface;
use Neusta\ConverterBundle\Populator;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Person;
use Neusta\ConverterBundle\Tests\Fixtures\Model\User;

/**
 * @implements Populator<User, Person, ContextInterface>
 */
class PersonNamePopulator implements Populator
{
    public function populate(object $target, object $source, ?ContextInterface $ctx = null): void
    {
        $separator = ' ';
        if ($ctx?->hasKey('separator')) {
            $separator = $ctx->getValue('separator');
        }

        $target->setFullName(implode(
            $separator,
            [
                $source->getFirstname(),
                $source->getLastname()
            ]
        ));
    }
}
