<?php

namespace App\Entity;

use App\Repository\BannerTextRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: BannerTextRepository::class)]
class BannerText
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups('page:getById')] 
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Groups('page:getById')] 
    private ?string $content_banner_text = null;

    #[ORM\ManyToOne(inversedBy: 'BannerTextsList')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Page $page = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContentBannerText(): ?string
    {
        return $this->content_banner_text;
    }

    public function setContentBannerText(string $content_banner_text): static
    {
        $this->content_banner_text = $content_banner_text;

        return $this;
    }

    public function getPage(): ?Page
    {
        return $this->page;
    }

    public function setPage(?Page $page): static
    {
        $this->page = $page;

        return $this;
    }
}
