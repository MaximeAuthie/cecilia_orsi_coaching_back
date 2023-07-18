<?php

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $name_category = null;

    #[ORM\Column(length: 50)]
    private ?string $color_category = null;

    #[ORM\ManyToMany(targetEntity: Article::class, mappedBy: 'categories_list')]
    private Collection $articles_list;

    public function __construct()
    {
        $this->articles_list = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNameCategory(): ?string
    {
        return $this->name_category;
    }

    public function setNameCategory(string $name_category): static
    {
        $this->name_category = $name_category;

        return $this;
    }

    public function getColorCategory(): ?string
    {
        return $this->color_category;
    }

    public function setColorCategory(string $color_category): static
    {
        $this->color_category = $color_category;

        return $this;
    }

    /**
     * @return Collection<int, Article>
     */
    public function getArticlesList(): Collection
    {
        return $this->articles_list;
    }

    public function addArticlesList(Article $articlesList): static
    {
        if (!$this->articles_list->contains($articlesList)) {
            $this->articles_list->add($articlesList);
            $articlesList->addCategoriesList($this);
        }

        return $this;
    }

    public function removeArticlesList(Article $articlesList): static
    {
        if ($this->articles_list->removeElement($articlesList)) {
            $articlesList->removeCategoriesList($this);
        }

        return $this;
    }
}
