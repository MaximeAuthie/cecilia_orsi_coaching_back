<?php

namespace App\Entity;

use App\Repository\VisitRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VisitRepository::class)]
class Visit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $time_visit = null;

    #[ORM\Column(length: 50)]
    private ?string $ip_visit = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTimeVisit(): ?\DateTimeInterface
    {
        return $this->time_visit;
    }

    public function setTimeVisit(\DateTimeInterface $time_visit): static
    {
        $this->time_visit = $time_visit;

        return $this;
    }

    public function getIpVisit(): ?string
    {
        return $this->ip_visit;
    }

    public function setIpVisit(string $ip_visit): static
    {
        $this->ip_visit = $ip_visit;

        return $this;
    }
}
