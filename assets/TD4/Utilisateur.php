<?php

namespace App\Entity;

use App\Repository\UtilisateurRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
#[ORM\UniqueConstraint(name: 'UTILISATEUR_UNIQ_IDENTIFIER_LOGIN', fields: ['login'])]
#[ORM\UniqueConstraint(name: 'UTILISATEUR_UNIQ_IDENTIFIER_EMAIL', fields: ['adresseEmail'])]
#[UniqueEntity(fields: ['login'], message: 'Ce login est déjà pris.')]
#[UniqueEntity(fields: ['adresseEmail'], message: 'Cette adresse email est déjà prise.')]
class Utilisateur //implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank]
    #[Assert\Length(
        min: 4,
        max: 20,
        minMessage: "Le login doit comporter au moins {{ limit }} caractères!",
        maxMessage: "Le login ne peut pas dépasser {{ limit }} caractères!")
    ]
    private ?string $login = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Email(message: "L'adresse email n'est pas valide!")]
    private ?string $adresseEmail = null;

    #[ORM\Column(options: ["default" => false])]
    private ?bool $premium = false;

    /**
     * @var list<string> The user roles
     */
    /*
    #[ORM\Column]
    private array $roles = [];
    */

    /**
     * @var string The hashed password
     */
    //#[ORM\Column]
    //private ?string $password = null;

    public function __construct()
    {

    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLogin(): ?string
    {
        return $this->login;
    }

    public function setLogin(string $login): static
    {
        $this->login = $login;

        return $this;
    }

    public function getAdresseEmail(): ?string
    {
        return $this->adresseEmail;
    }

    public function setAdresseEmail(string $adresseEmail): static
    {
        $this->adresseEmail = $adresseEmail;

        return $this;
    }

    public function isPremium(): ?bool
    {
        return $this->premium;
    }

    public function setPremium(bool $premium): static
    {
        $this->premium = $premium;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
	/*
    public function getUserIdentifier(): string
    {
        return (string) $this->login;
    }*/

    /**
     * @see UserInterface
     */
	/*
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }*/

    /**
     * @param list<string> $roles
     */
    /*public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }*/

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    /*public function getPassword(): ?string
    {
        return $this->password;
    }*/

    /*public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }*/

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    /*public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);

        return $data;
    }*/

    /*
    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }*/
}
