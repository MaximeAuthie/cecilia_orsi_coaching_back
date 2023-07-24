<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230724101741 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE article CHANGE is_published is_published_article TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE comment CHANGE is_validated is_validated_comment TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE article CHANGE is_published_article is_published TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE comment CHANGE is_validated_comment is_validated TINYINT(1) NOT NULL');
    }
}
