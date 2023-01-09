<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Fixtures\Model;

class PersonAddress
{
    private string $postalCode;
    private string $city;
    private string $street;
    private string $streetNo;

    /**
     * @return string
     */
    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    /**
     * @param string $postalCode
     * @return Address
     */
    public function setPostalCode(string $postalCode): PersonAddress
    {
        $this->postalCode = $postalCode;
        return $this;
    }

    /**
     * @return string
     */
    public function getCity(): string
    {
        return $this->city;
    }

    /**
     * @param string $city
     * @return Address
     */
    public function setCity(string $city): PersonAddress
    {
        $this->city = $city;
        return $this;
    }

    /**
     * @return string
     */
    public function getStreet(): string
    {
        return $this->street;
    }

    /**
     * @param string $street
     * @return Address
     */
    public function setStreet(string $street): PersonAddress
    {
        $this->street = $street;
        return $this;
    }

    /**
     * @return string
     */
    public function getStreetNo(): string
    {
        return $this->streetNo;
    }

    /**
     * @param string $streetNo
     * @return Address
     */
    public function setStreetNo(string $streetNo): PersonAddress
    {
        $this->streetNo = $streetNo;
        return $this;
    }

}