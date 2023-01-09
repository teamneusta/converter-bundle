<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Fixtures\Model;

class Person
{
    private string $fullName;

    private int $age;

    private PersonAddress $address;

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): void
    {
        $this->fullName = $fullName;
    }

    public function getAge(): int
    {
        return $this->age;
    }

    public function setAge(int $age): void
    {
        $this->age = $age;
    }

    public function getAddress(): PersonAddress
    {
        return $this->address;
    }

    public function setAddress(PersonAddress $address): Person
    {
        $this->address = $address;
        return $this;
    }
}
