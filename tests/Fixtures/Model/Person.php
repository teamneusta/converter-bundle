<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Fixtures\Model;

class Person
{
    private ?string $fullName = null;

    private ?int $age = null;

    private ?string $locale = null;

    private ?string $group = null;

    private ?PersonAddress $address = null;

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(?string $fullName): Person
    {
        $this->fullName = $fullName;
        return $this;
    }

    public function getAge(): ?int
    {
        return $this->age;
    }

    public function setAge(?int $age): Person
    {
        $this->age = $age;
        return $this;
    }

    public function getAddress(): ?PersonAddress
    {
        return $this->address;
    }

    public function setAddress(?PersonAddress $address): Person
    {
        $this->address = $address;
        return $this;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(?string $locale): Person
    {
        $this->locale = $locale;
        return $this;
    }

    public function getGroup(): ?string
    {
        return $this->group;
    }

    public function setGroup(?string $group): Person
    {
        $this->group = $group;
        return $this;
    }
}
