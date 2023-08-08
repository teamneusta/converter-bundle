<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Fixtures\Model;

class Phone
{
    private string $type;
    private string $number;

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return Phone
     */
    public function setType(string $type): Phone
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string
     */
    public function getNumber(): string
    {
        return $this->number;
    }

    /**
     * @param string $number
     * @return Phone
     */
    public function setNumber(string $number): Phone
    {
        $this->number = $number;
        return $this;
    }

}
