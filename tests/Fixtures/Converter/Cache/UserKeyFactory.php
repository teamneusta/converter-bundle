<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Fixtures\Converter\Cache;

use Neusta\ConverterBundle\Converter\Cache\CacheKeyFactory;
use Neusta\ConverterBundle\Tests\Fixtures\Model\User;

/**
 * @implements CacheKeyFactory<User>
 */
class UserKeyFactory implements CacheKeyFactory
{
    public function createCacheKeyFor(object $source): string
    {
        return (string) $source->getUuid();
    }
}
