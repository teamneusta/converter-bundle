<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Populator;

use Neusta\ConverterBundle\Converter\ConverterContext;
use Neusta\ConverterBundle\Populator\Populator;
use Neusta\ConverterBundle\Tests\Model\Person;
use Neusta\ConverterBundle\Tests\Model\User;

/**
 * @implements Populator<User, Person>
 */
class PersonNamePopulator implements Populator
{
    public function populate(object $target, object $source, ?ConverterContext $ctx): void
    {
        $separationString = ' ';
        if ($ctx && $ctx->hasKey('separation char')) {
            $separationString = $ctx->getValue('separation char');
        }
        $target->setFullName($source->getFirstname() . $separationString . $source->getLastname());
    }
}
