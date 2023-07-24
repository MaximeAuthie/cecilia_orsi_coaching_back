<?php

namespace App\Entity;

use App\Repository\CommentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CommentRepository::class)]
class Comment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['comment:getAll'])]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Groups(['comment:getAll'])]
    private ?string $author_name_comment = null;

    #[ORM\Column(length: 100)]
    #[Groups(['comment:getAll'])]
    private ?string $author_email_comment = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['comment:getAll'])]
    private ?\DateTimeInterface $date_comment = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['comment:getAll'])]
    private ?string $content_comment = null;

    #[ORM\ManyToOne(inversedBy: 'comments_list')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['comment:getAll'])]
    private ?Article $article = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    #[ORM\Column]
    private ?bool $isValidated_comment = null;

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

    public function isIsValidatedComment(): ?bool
    {
        return $this->isValidated_comment;
    }

    public function setIsValidatedComment(bool $isValidated_comment): static
    {
        $this->isValidated_comment = $isValidated_comment;

        return $this;
    }
}
