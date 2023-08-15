<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Fixtures\Model;

class Hobby
{
    private string $label;

    private string $category;

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @param string $label
     * @return Hobby
     */
    public function setLabel(string $label): Hobby
    {
        $this->label = $label;
        return $this;
    }

    /**
     * @return string
     */
    public function getCategory(): string
    {
        return $this->category;
    }

    /**
     * @param string $category
     * @return Hobby
     */
    public function setCategory(string $category): Hobby
    {
        $this->category = $category;
        return $this;
    }

    public function __toString(): string
    {
        return $this->label . ' hobby_as_string';
    }
}
