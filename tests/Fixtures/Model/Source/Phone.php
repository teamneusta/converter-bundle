<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Fixtures\Model\Source;

class Phone
{
    private string $type;
    private string $number;

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    public function setNumber(string $number): self
    {
        $this->number = $number;

        return $this;
    }
}
