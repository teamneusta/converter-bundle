<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Fixtures\Converter\Cache;

use Neusta\ConverterBundle\Converter\Cache\CacheKeyFactory;
use Neusta\ConverterBundle\Converter\DefaultConverterContext;
use Neusta\ConverterBundle\Tests\Fixtures\Model\User;

/**
 * @implements CacheKeyFactory<User, DefaultConverterContext>
 */
class UserKeyFactory implements CacheKeyFactory
{
    public function createFor(object $source, ?object $ctx = null): string
    {
        return $source->getUuid() . $ctx?->getHash();
    }
}
