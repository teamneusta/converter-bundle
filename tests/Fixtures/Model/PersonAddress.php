<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Fixtures\Model;

class PersonAddress
{
    private string $postalCode;
    private string $city;
    private string $street;
    private string $streetNo;

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    public function setPostalCode(string $postalCode): PersonAddress
    {
        $this->postalCode = $postalCode;
        return $this;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setCity(string $city): PersonAddress
    {
        $this->city = $city;
        return $this;
    }

    public function getStreet(): string
    {
        return $this->street;
    }

    public function setStreet(string $street): PersonAddress
    {
        $this->street = $street;
        return $this;
    }

    public function getStreetNo(): string
    {
        return $this->streetNo;
    }

    public function setStreetNo(string $streetNo): PersonAddress
    {
        $this->streetNo = $streetNo;
        return $this;
    }
}
