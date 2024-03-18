<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Fixtures\Model\Source;

use Neusta\ConverterBundle\Tests\Fixtures\Model\UnknownType;

class User
{
    private int $uuid;
    private string $firstname;
    private string $lastname;
    private ?string $fullName;
    private ?int $ageInYears;
    private string $email;
    private ?Address $address;

    /** @var array<string> */
    private array $favouriteMovies;

    /** @var array<Hobby>|null */
    private ?array $hobbies;

    /** @var array<Phone> */
    private array $phones;

    private UnknownType $fieldWithUnknownType;

    public function getUuid(): int
    {
        return $this->uuid;
    }

    public function setUuid(int $uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getFirstname(): string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): self
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(?string $fullName): self
    {
        $this->fullName = $fullName;

        return $this;
    }

    public function getAgeInYears(): ?int
    {
        return $this->ageInYears;
    }

    public function setAgeInYears(?int $ageInYears): self
    {
        $this->ageInYears = $ageInYears;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getAddress(): Address
    {
        return $this->address;
    }

    public function setAddress(?Address $address): self
    {
        $this->address = $address;

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

    public function getHobbies(): ?array
    {
        return $this->hobbies;
    }

    public function setHobbies(?array $hobbies): self
    {
        $this->hobbies = $hobbies;

        return $this;
    }

    public function getFieldWithUnknownType(): UnknownType
    {
        return $this->fieldWithUnknownType;
    }

    public function setFieldWithUnknownType(UnknownType $fieldWithUnknownType): self
    {
        $this->fieldWithUnknownType = $fieldWithUnknownType;

        return $this;
    }

    /**
     * @return array<Phone>
     */
    public function getPhones(): array
    {
        return $this->phones;
    }

    /**
     * @param array<Phone> $phones
     */
    public function setPhones(array $phones): self
    {
        $this->phones = $phones;

        return $this;
    }
}
