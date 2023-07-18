<?php

namespace App\Entity;

use App\Repository\CommentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommentRepository::class)]
class Comment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $author_name_comment = null;

    #[ORM\Column(length: 100)]
    private ?string $author_email_comment = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $date_comment = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $content_comment = null;

    #[ORM\ManyToOne(inversedBy: 'comments_list')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Article $article = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAuthorNameComment(): ?string
    {
        return $this->author_name_comment;
    }

    public function setAuthorNameComment(string $author_name_comment): static
    {
        $this->author_name_comment = $author_name_comment;

        return $this;
    }

    public function getAuthorEmailComment(): ?string
    {
        return $this->author_email_comment;
    }

    public function setAuthorEmailComment(string $author_email_comment): static
    {
        $this->author_email_comment = $author_email_comment;

        return $this;
    }

    public function getDateComment(): ?\DateTimeInterface
    {
        return $this->date_comment;
    }

    public function setDateComment(\DateTimeInterface $date_comment): static
    {
        $this->date_comment = $date_comment;

        return $this;
    }

    public function getContentComment(): ?string
    {
        return $this->content_comment;
    }

    public function setContentComment(string $content_comment): static
    {
        $this->content_comment = $content_comment;

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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }
}
