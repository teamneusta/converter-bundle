<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Fixtures\Model\Target;

class Person
{
    private ?string $fullName = null;

    private ?int $age = null;

    private ?string $locale = null;

    private ?string $group = null;

    private ?string $mail = null;

    private ?PersonAddress $address = null;

    private ?string $placeOfResidence = null;

    /** @var array<string> */
    private array $favouriteMovies;

    /** @var array<string> */
    private array $activities;

    /** @var array<ContactNumber> */
    private array $contactNumbers;

    public function getContactNumbers(): array
    {
        return $this->contactNumbers;
    }

    public function setContactNumbers(array $contactNumbers): self
    {
        $this->contactNumbers = $contactNumbers;

        return $this;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function setFullName(?string $fullName): self
    {
        $this->fullName = $fullName;

        return $this;
    }

    public function getAge(): ?int
    {
        return $this->age;
    }

    public function setAge(?int $age): self
    {
        $this->age = $age;

        return $this;
    }

    public function getAddress(): ?PersonAddress
    {
        return $this->address;
    }

    public function setAddress(?PersonAddress $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getPlaceOfResidence(): ?string
    {
        return $this->placeOfResidence;
    }

    public function setPlaceOfResidence(?string $placeOfResidence): self
    {
        $this->placeOfResidence = $placeOfResidence;

        return $this;
    }

    public function getFavouriteMovies(): array
    {
        return $this->favouriteMovies;
    }

    public function setFavouriteMovies(array $favouriteMovies): self
    {
        $this->favouriteMovies = $favouriteMovies;

        return $this;
    }

    public function getActivities(): array
    {
        return $this->activities;
    }

    public function setActivities(array $activities): self
    {
        $this->activities = $activities;

        return $this;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(?string $locale): self
    {
        $this->locale = $locale;

        return $this;
    }

    public function getGroup(): ?string
    {
        return $this->group;
    }

    public function setGroup(?string $group): self
    {
        $this->group = $group;

        return $this;
    }
}
