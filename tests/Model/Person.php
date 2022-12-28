<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Model;

class Person
{
    private string $fullName;

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): void
    {
        $this->fullName = $fullName;
    }
}
