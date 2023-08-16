<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups('user:getAll')]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Groups('user:getAll')]
    private ?string $email = null;

    #[ORM\Column]
    #[Groups('user:getAll')]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 50)]
    #[Groups(['article:getAll','user:getAll'])]
    private ?string $first_name_user = null;

    #[ORM\Column(length: 50)]
    #[Groups(['article:getAll','user:getAll'])]
    private ?string $last_name_user = null;

    #[ORM\Column]
    private ?bool $isActive_user = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $last_auth_user = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $last_update_user = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getFirstNameUser(): ?string
    {
        return $this->first_name_user;
    }

    public function setFirstNameUser(string $first_name_user): static
    {
        $this->first_name_user = $first_name_user;

        return $this;
    }

    public function getLastNameUser(): ?string
    {
        return $this->last_name_user;
    }

    public function setLastNameUser(string $last_name_user): static
    {
        $this->last_name_user = $last_name_user;

        return $this;
    }

    public function isIsActiveUser(): ?bool
    {
        return $this->isActive_user;
    }

    public function setIsActiveUser(bool $isActive_user): static
    {
        $this->isActive_user = $isActive_user;

        return $this;
    }

    public function getLastAuthUser(): ?\DateTimeInterface
    {
        return $this->last_auth_user;
    }

    public function setLastAuthUser(\DateTimeInterface $last_auth_user): static
    {
        $this->last_auth_user = $last_auth_user;

        return $this;
    }

    public function getLastUpdateUser(): ?\DateTimeInterface
    {
        return $this->last_update_user;
    }

    public function setLastUpdateUser(?\DateTimeInterface $last_update_user): static
    {
        $this->last_update_user = $last_update_user;

        return $this;
    }

}
