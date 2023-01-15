<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Fixtures\Model;

class Address
{
    private string $postalCode;
    private string $city;
    private string $street;
    private string $streetNo;

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    public function setPostalCode(string $postalCode): Address
    {
        $this->postalCode = $postalCode;
        return $this;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setCity(string $city): Address
    {
        $this->city = $city;
        return $this;
    }

    public function getStreet(): string
    {
        return $this->street;
    }

    public function setStreet(string $street): Address
    {
        $this->street = $street;
        return $this;
    }

    public function getStreetNo(): string
    {
        return $this->streetNo;
    }

    public function setStreetNo(string $streetNo): Address
    {
        $this->streetNo = $streetNo;
        return $this;
    }
}
