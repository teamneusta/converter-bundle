<?php
declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Fixtures\Context;

final class LanguageContext
{
    public function __construct(
        public readonly string $language,
    ) {
    }
}
