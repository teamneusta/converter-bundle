<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Fixtures\Model;

class User
{
    private string $firstname;
    private string $lastname;
    private int $uuid;
    private Address $address;

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

    /**
     * @return Address
     */
    public function getAddress(): Address
    {
        return $this->address;
    }

    /**
     * @param Address $address
     * @return User
     */
    public function setAddress(Address $address): User
    {
        $this->address = $address;
        return $this;
    }
}
