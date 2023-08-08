<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Fixtures\Model;

class User
{
    private int $uuid;
    private string $firstname;
    private string $lastname;
    private string $fullName;
    private int $ageInYears;
    private Address $address;

    /** @var array<string> */
    private array $favouriteMovies;

    /** @var array<Hobby> */
    private array $hobbies;

    /** @var array<Phone> */
    private array $phones;

    private UnknownType $fieldWithUnknownType;

    public function getUuid(): int
    {
        return $this->uuid;
    }

    public function setUuid(int $uuid): User
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getFirstname(): string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): User
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): User
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): User
    {
        $this->fullName = $fullName;
        return $this;
    }

    public function getAgeInYears(): int
    {
        return $this->ageInYears;
    }

    public function setAgeInYears($ageInYears): User
    {
        $this->ageInYears = $ageInYears;
        return $this;
    }

    public function getAddress(): Address
    {
        return $this->address;
    }

    public function setAddress(Address $address): User
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
     * @return User
     */
    public function setFavouriteMovies(array $favouriteMovies): User
    {
        $this->favouriteMovies = $favouriteMovies;
        return $this;
    }

    /**
     * @return array
     */
    public function getHobbies(): array
    {
        return $this->hobbies;
    }

    /**
     * @param array $hobbies
     * @return User
     */
    public function setHobbies(array $hobbies): User
    {
        $this->hobbies = $hobbies;
        return $this;
    }

    public function getFieldWithUnknownType(): UnknownType
    {
        return $this->fieldWithUnknownType;
    }
    public function setFieldWithUnknownType(UnknownType $fieldWithUnknownType): User
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
     * @return User
     */
    public function setPhones(array $phones): User
    {
        $this->phones = $phones;
        return $this;
    }

}
