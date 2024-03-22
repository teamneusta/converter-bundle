<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Fixtures\Populator;

use Neusta\ConverterBundle\Converter\Context\GenericContext;
use Neusta\ConverterBundle\Populator;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Source\User;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Target\Person;

/**
 * @implements Populator<User, Person, GenericContext>
 */
class PersonNamePopulator implements Populator
{
    public function populate(object $target, object $source, ?object $ctx = null): void
    {
        $separator = ' ';
        if ($ctx?->hasKey('separator')) {
            $separator = $ctx->getValue('separator');
        }

        $target->setFullName(implode(
            $separator,
            [
                $source->getFirstname(),
                $source->getLastname(),
            ]
        ));
    }
}
