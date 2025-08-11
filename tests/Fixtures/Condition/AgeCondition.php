<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Fixtures\Condition;

class AgeCondition
{
    public function __construct(
        private int $minAge,
    ) {
    }

    public function __invoke($target, $source, $ctx): bool
    {
        return $source->getAgeInYears() >= $this->minAge;
    }
}
