<?php

namespace App\Entity;

use App\Repository\KeywordRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: KeywordRepository::class)]
class Keyword
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $content_keywork = null;

    #[ORM\ManyToOne(inversedBy: 'kewords_list')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Article $article = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContentKeywork(): ?string
    {
        return $this->content_keywork;
    }

    public function setContentKeywork(string $content_keywork): static
    {
        $this->content_keywork = $content_keywork;

        return $this;
    }

    public function getArticle(): ?Article
    {
        return $this->article;
    }

    public function setArticle(?Article $article): static
    {
        $this->article = $article;

        return $this;
    }
}
