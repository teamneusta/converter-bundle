<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Fixtures\Model;

class ContactNumber
{
    private string $phoneNumber;

    /**
     * @return string
     */
    public function getPhoneNumber(): string
    {
        return $this->phoneNumber;
    }

    /**
     * @param string $phoneNumber
     * @return ContactNumber
     */
    public function setPhoneNumber(string $phoneNumber): ContactNumber
    {
        $this->phoneNumber = $phoneNumber;
        return $this;
    }

}
