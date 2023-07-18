<?php

namespace App\Entity;

use App\Repository\TileRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TileRepository::class)]
class Tile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $title_tile = null;

    #[ORM\Column(length: 255)]
    private ?string $img_url_tile = null;

    #[ORM\Column(length: 100)]
    private ?string $link_tile = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitleTile(): ?string
    {
        return $this->title_tile;
    }

    public function setTitleTile(string $title_tile): static
    {
        $this->title_tile = $title_tile;

        return $this;
    }

    public function getImgUrlTile(): ?string
    {
        return $this->img_url_tile;
    }

    public function setImgUrlTile(string $img_url_tile): static
    {
        $this->img_url_tile = $img_url_tile;

        return $this;
    }

    public function getLinkTile(): ?string
    {
        return $this->link_tile;
    }

    public function setLinkTile(string $link_tile): static
    {
        $this->link_tile = $link_tile;

        return $this;
    }
}
