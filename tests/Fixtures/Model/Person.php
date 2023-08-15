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

    /** @var array<string> */
    private array $favouriteMovies;

    /** @var array<string> */
    private array $activities;

    /** @var array<ContactNumber> */
    private array $contactNumbers;

    /**
     * @return array
     */
    public function getContactNumbers(): array
    {
        return $this->contactNumbers;
    }

    /**
     * @param array $contactNumbers
     * @return Person
     */
    public function setContactNumbers(array $contactNumbers): Person
    {
        $this->contactNumbers = $contactNumbers;
        return $this;
    }

    public function getFullName(): string
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

    /**
     * @return array
     */
    public function getFavouriteMovies(): array
    {
        return $this->favouriteMovies;
    }

    /**
     * @param array $favouriteMovies
     * @return Person
     */
    public function setFavouriteMovies(array $favouriteMovies): Person
    {
        $this->favouriteMovies = $favouriteMovies;
        return $this;
    }

    /**
     * @return array
     */
    public function getActivities(): array
    {
        return $this->activities;
    }

    /**
     * @param array $activities
     * @return Person
     */
    public function setActivities(array $activities): Person
    {
        $this->activities = $activities;
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
