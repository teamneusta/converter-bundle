<?php


namespace Neusta\ConverterBundle\CacheManagement;

/**
 * @template S of object
 */
interface CacheKeyFactory
{
    /**
     * @param S $source
     * @return string
     */
    public function createCacheKey(object $source): string;
}
