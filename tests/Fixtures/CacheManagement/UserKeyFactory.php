<?php

namespace Neusta\ConverterBundle\Tests\Fixtures\CacheManagement;

use Neusta\ConverterBundle\CacheManagement\CacheKeyFactory;
use Neusta\ConverterBundle\Tests\Fixtures\Model\User;

/**
 * @implements CacheKeyFactory<User>
 */
class UserKeyFactory implements CacheKeyFactory
{
    public function createCacheKey(object $source): string
    {
        return (string) $source->getUuid();
    }
}
