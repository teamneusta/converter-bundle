<?php

namespace Neusta\ConverterBundle\Tests\CacheManagement;

use Neusta\ConverterBundle\CacheManagement\CacheKeyFactory;
use Neusta\ConverterBundle\Tests\Model\User;

/**
 * @implements CacheKeyFactory<User>
 */
class UserKeyFactory implements CacheKeyFactory
{

    /**
     * @inheritDoc
     */
    public function createCacheKey(object $source): string
    {
        return (string) $source->getUuid();
    }
}