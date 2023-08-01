<?php

namespace App\Entity;

use App\Repository\PageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: PageRepository::class)]
class Page
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups('page:getAll')] 
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Groups('page:getAll')] 
    private ?string $title_page = null;

    #[ORM\Column(length: 255)]
    #[Groups('page:getAll')] 
    private ?string $banner_url_page = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups('page:getAll')] 
    private ?string $img1_url_page = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups('page:getAll')] 
    private ?string $img2_url_page = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups('page:getAll')] 
    private ?string $text1_page = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups('page:getAll')] 
    private ?string $text2_page = null;

    #[ORM\Column]
    #[Groups('page:getAll')] 
    private ?bool $isMainButtonActive_page = null;

    #[ORM\Column]
    #[Groups('page:getAll')] 
    private ?bool $isSecondaryButtonActive_page = null;

    #[ORM\ManyToMany(targetEntity: Tile::class)]
    #[Groups('page:getAll')] 
    private Collection $tiles_list;

    #[ORM\OneToMany(mappedBy: 'page', targetEntity: BannerText::class, cascade: ["persist"])] //! cascade indipensable pour permettre l'ajout de nouveau BannerText en BDD dans l'updatde la page
    #[Groups('page:getAll')] 
    private Collection $BannerTextsList;

    public function __construct()
    {
        $this->tiles_list = new ArrayCollection();
        $this->BannerTextsList = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitlePage(): ?string
    {
        return $this->title_page;
    }

    public function setTitlePage(string $title_page): static
    {
        $this->title_page = $title_page;

        return $this;
    }

    public function getBannerUrlPage(): ?string
    {
        return $this->banner_url_page;
    }

    public function setBannerUrlPage(string $banner_url_page): static
    {
        $this->banner_url_page = $banner_url_page;

        return $this;
    }

    public function getImg1UrlPage(): ?string
    {
        return $this->img1_url_page;
    }

    public function setImg1UrlPage(string|null $img1_url_page): static
    {
        $this->img1_url_page = $img1_url_page;

        return $this;
    }

    public function getImg2UrlPage(): ?string
    {
        return $this->img2_url_page;
    }

    public function setImg2UrlPage(string|null $img2_url_page): static
    {
        $this->img2_url_page = $img2_url_page;

        return $this;
    }

    public function getText1Page(): ?string
    {
        return $this->text1_page;
    }

    public function setText1Page(?string $text1_page): static
    {
        $this->text1_page = $text1_page;

        return $this;
    }

    public function getText2Page(): ?string
    {
        return $this->text2_page;
    }

    public function setText2Page(?string $text2_page): static
    {
        $this->text2_page = $text2_page;

        return $this;
    }

    public function isIsMainButtonActivePage(): ?bool
    {
        return $this->isMainButtonActive_page;
    }

    public function setIsMainButtonActivePage(bool $isMainButtonActive_page): static
    {
        $this->isMainButtonActive_page = $isMainButtonActive_page;

        return $this;
    }

    public function isIsSecondaryButtonActivePage(): ?bool
    {
        return $this->isSecondaryButtonActive_page;
    }

    public function setIsSecondaryButtonActivePage(bool $isSecondaryButtonActive_page): static
    {
        $this->isSecondaryButtonActive_page = $isSecondaryButtonActive_page;

        return $this;
    }

    /**
     * @return Collection<int, Tile>
     */
    public function getTilesList(): Collection
    {
        return $this->tiles_list;
    }

    public function addTilesList(Tile $tilesList): static
    {
        if (!$this->tiles_list->contains($tilesList)) {
            $this->tiles_list->add($tilesList);
        }

        return $this;
    }

    public function removeTilesList(Tile $tilesList): static
    {
        $this->tiles_list->removeElement($tilesList);

        return $this;
    }

    /**
     * @return Collection<int, BannerText>
     */
    public function getBannerTextsList(): Collection
    {
        return $this->BannerTextsList;
    }

    public function addBannerTextsList(BannerText $bannerTextsList): static
    {
        if (!$this->BannerTextsList->contains($bannerTextsList)) {
            $this->BannerTextsList->add($bannerTextsList);
            $bannerTextsList->setPage($this);
        }

        return $this;
    }

    public function removeBannerTextsList(BannerText $bannerTextsList): static
    {
        if ($this->BannerTextsList->removeElement($bannerTextsList)) {
            // set the owning side to null (unless already changed)
            if ($bannerTextsList->getPage() === $this) {
                $bannerTextsList->setPage(null);
            }
        }

        return $this;
    }
}
