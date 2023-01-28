<?php

declare(strict_types=1);


namespace Neusta\ConverterBundle\Converter\Cache;

/**
 * @template TSource of object
 */
interface CacheKeyFactory
{
    /**
     * @param TSource $source
     *
     * @return non-empty-string
     */
    public function createFor(object $source): string;
}
