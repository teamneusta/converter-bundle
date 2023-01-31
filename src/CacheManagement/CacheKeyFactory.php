<?php

declare(strict_types=1);


namespace Neusta\ConverterBundle\CacheManagement;

/**
 * @template TSource of object
 */
interface CacheKeyFactory
{
    /**
     * @param TSource $source
     */
    public function createCacheKey(object $source): string;
}
