<?php

namespace App\Entity;

use App\Repository\KeywordRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: KeywordRepository::class)]
class Keyword
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups('article:getAll')]
    private ?int $id = null;
    
    
    #[ORM\Column(length: 50)]
    #[Groups('article:getAll')]
    private ?string $content_keywork = null;

    #[ORM\ManyToOne(inversedBy: 'kewords_list', cascade: ["persist"])] //! cascade indipensable pour permettre l'ajout de nouveau BannerText en BDD dans l'updatde la page
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
