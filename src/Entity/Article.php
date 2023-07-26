<?php

namespace App\Entity;

use App\Repository\ArticleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ArticleRepository::class)]
class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['article:getAll', 'comment:getAll', 'comment:getToValidate'])]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Groups(['article:getAll', 'comment:getToValidate'])]
    private ?string $title_article = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups('article:getAll')]
    private ?\DateTimeInterface $date_article = null;

    #[ORM\Column(length: 255)]
    #[Groups('article:getAll')]
    private ?string $banner_url_article = null;

    #[ORM\Column(length: 255)]
    #[Groups('article:getAll')]
    private ?string $description_article = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups('article:getAll')]
    private ?string $summary_article = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups('article:getAll')]
    private ?string $content_article = null;

    #[ORM\ManyToMany(targetEntity: Category::class, inversedBy: 'articles_list')]
    #[Groups('article:getAll')]
    private Collection $categories_list;

    #[ORM\OneToMany(mappedBy: 'article', targetEntity: Comment::class)]
    private Collection $comments_list;

    #[ORM\OneToMany(mappedBy: 'article', targetEntity: Keyword::class)]
    private Collection $kewords_list;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups('article:getAll')]
    private ?User $user = null;

    #[ORM\Column]
    private ?bool $isPublished_article = null;

    public function __construct()
    {
        $this->categories_list = new ArrayCollection();
        $this->comments_list = new ArrayCollection();
        $this->kewords_list = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitleArticle(): ?string
    {
        return $this->title_article;
    }

    public function setTitleArticle(string $title_article): static
    {
        $this->title_article = $title_article;

        return $this;
    }

    public function getDateArticle(): ?\DateTimeInterface
    {
        return $this->date_article;
    }

    public function setDateArticle(\DateTimeInterface $date_article): static
    {
        $this->date_article = $date_article;

        return $this;
    }

    public function getBannerUrlArticle(): ?string
    {
        return $this->banner_url_article;
    }

    public function setBannerUrlArticle(string $banner_url_article): static
    {
        $this->banner_url_article = $banner_url_article;

        return $this;
    }

    public function getDescriptionArticle(): ?string
    {
        return $this->description_article;
    }

    public function setDescriptionArticle(string $description_article): static
    {
        $this->description_article = $description_article;

        return $this;
    }

    public function getSummaryArticle(): ?string
    {
        return $this->summary_article;
    }

    public function setSummaryArticle(string $summary_article): static
    {
        $this->summary_article = $summary_article;

        return $this;
    }

    public function getContentArticle(): ?string
    {
        return $this->content_article;
    }

    public function setContentArticle(string $content_article): static
    {
        $this->content_article = $content_article;

        return $this;
    }

    /**
     * @return Collection<int, Category>
     */
    public function getCategoriesList(): Collection
    {
        return $this->categories_list;
    }

    public function addCategoriesList(Category $categoriesList): static
    {
        if (!$this->categories_list->contains($categoriesList)) {
            $this->categories_list->add($categoriesList);
        }

        return $this;
    }

    public function removeCategoriesList(Category $categoriesList): static
    {
        $this->categories_list->removeElement($categoriesList);

        return $this;
    }

    /**
     * @return Collection<int, Comment>
     */
    public function getCommentsList(): Collection
    {
        return $this->comments_list;
    }

    public function addCommentsList(Comment $commentsList): static
    {
        if (!$this->comments_list->contains($commentsList)) {
            $this->comments_list->add($commentsList);
            $commentsList->setArticle($this);
        }

        return $this;
    }

    public function removeCommentsList(Comment $commentsList): static
    {
        if ($this->comments_list->removeElement($commentsList)) {
            // set the owning side to null (unless already changed)
            if ($commentsList->getArticle() === $this) {
                $commentsList->setArticle(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Keyword>
     */
    public function getKewordsList(): Collection
    {
        return $this->kewords_list;
    }

    public function addKewordsList(Keyword $kewordsList): static
    {
        if (!$this->kewords_list->contains($kewordsList)) {
            $this->kewords_list->add($kewordsList);
            $kewordsList->setArticle($this);
        }

        return $this;
    }

    public function removeKewordsList(Keyword $kewordsList): static
    {
        if ($this->kewords_list->removeElement($kewordsList)) {
            // set the owning side to null (unless already changed)
            if ($kewordsList->getArticle() === $this) {
                $kewordsList->setArticle(null);
            }
        }

        return $this;
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

    public function isIsPublished(): ?bool
    {
        return $this->isPublished_article;
    }

    public function setIsPublished(bool $isPublished_article): static
    {
        $this->isPublished_article = $isPublished_article;

        return $this;
    }
}
