<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Fixtures\Model;

class User
{
    private int $uuid;
    private string $firstname;
    private string $lastname;
    private string $fullName;
    private int $ageInYears;
    private Address $address;

    private UnknownType $fieldWithUnknownType;

    public function getUuid(): int
    {
        return $this->uuid;
    }

    public function setUuid(int $uuid): User
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getFirstname(): string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): User
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): User
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): User
    {
        $this->fullName = $fullName;
        return $this;
    }

    public function getAgeInYears(): int
    {
        return $this->ageInYears;
    }

    public function setAgeInYears($ageInYears): User
    {
        $this->ageInYears = $ageInYears;
        return $this;
    }

    public function getAddress(): Address
    {
        return $this->address;
    }

    public function setAddress(Address $address): User
    {
        $this->address = $address;
        return $this;
    }

    public function getFieldWithUnknownType(): UnknownType
    {
        return $this->fieldWithUnknownType;
    }

    public function setFieldWithUnknownType(UnknownType $fieldWithUnknownType): User
    {
        $this->fieldWithUnknownType = $fieldWithUnknownType;
        return $this;
    }
}
