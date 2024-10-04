<?php

namespace App\Entity;

use App\Repository\InfoUserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: InfoUserRepository::class)]
class InfoUser
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'infoUser', cascade: ['persist', 'remove'])]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le prénom est obligatoire")]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le nom est obligatoire")]
    private ?string $lastName = null;

    #[ORM\Column(nullable: true)]
    #[Assert\GreaterThanOrEqual(value: 1000,
        message: "Le code postal doit être supérieur à 01000"
        )]
    #[Assert\LessThan(
        value: 100000,
        message: "Le code postal doit être inférieur à 100000"
        )]
    #[Assert\NotBlank(message: "Le code postal est obligatoire")]
    private ?int $zipCode = null;

    // la ville est optionnelle car le plus important est le code postal
    // qi aucune ville n'est renseignée, le code postal est utilisé pour trouver la ville
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $city = null;

    // par défaut en France 
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $country = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getZipCode(): ?int
    {
        return $this->zipCode;
    }

    public function setZipCode(?int $zipCode): static
    {
        $this->zipCode = $zipCode;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): static
    {
        $this->country = $country;

        return $this;
    }
}
