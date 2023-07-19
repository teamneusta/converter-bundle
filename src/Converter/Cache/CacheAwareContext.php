<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Converter\Cache;

interface CacheAwareContext
{
    /**
     * @return non-empty-string
     */
    public function getHash(): string;
}
