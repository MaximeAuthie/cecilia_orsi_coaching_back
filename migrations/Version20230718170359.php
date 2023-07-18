<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230718170359 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE article_category (article_id INT NOT NULL, category_id INT NOT NULL, INDEX IDX_53A4EDAA7294869C (article_id), INDEX IDX_53A4EDAA12469DE2 (category_id), PRIMARY KEY(article_id, category_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE page_tile (page_id INT NOT NULL, tile_id INT NOT NULL, INDEX IDX_F14BB5DEC4663E4 (page_id), INDEX IDX_F14BB5DE638AF48B (tile_id), PRIMARY KEY(page_id, tile_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE article_category ADD CONSTRAINT FK_53A4EDAA7294869C FOREIGN KEY (article_id) REFERENCES article (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE article_category ADD CONSTRAINT FK_53A4EDAA12469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE page_tile ADD CONSTRAINT FK_F14BB5DEC4663E4 FOREIGN KEY (page_id) REFERENCES page (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE page_tile ADD CONSTRAINT FK_F14BB5DE638AF48B FOREIGN KEY (tile_id) REFERENCES tile (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE article ADD user_id INT NOT NULL');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_23A0E66A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_23A0E66A76ED395 ON article (user_id)');
        $this->addSql('ALTER TABLE banner_text ADD page_id INT NOT NULL');
        $this->addSql('ALTER TABLE banner_text ADD CONSTRAINT FK_3332F6ADC4663E4 FOREIGN KEY (page_id) REFERENCES page (id)');
        $this->addSql('CREATE INDEX IDX_3332F6ADC4663E4 ON banner_text (page_id)');
        $this->addSql('ALTER TABLE comment ADD article_id INT NOT NULL, ADD user_id INT NOT NULL');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526C7294869C FOREIGN KEY (article_id) REFERENCES article (id)');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_9474526C7294869C ON comment (article_id)');
        $this->addSql('CREATE INDEX IDX_9474526CA76ED395 ON comment (user_id)');
        $this->addSql('ALTER TABLE keyword ADD article_id INT NOT NULL');
        $this->addSql('ALTER TABLE keyword ADD CONSTRAINT FK_5A93713B7294869C FOREIGN KEY (article_id) REFERENCES article (id)');
        $this->addSql('CREATE INDEX IDX_5A93713B7294869C ON keyword (article_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE article_category DROP FOREIGN KEY FK_53A4EDAA7294869C');
        $this->addSql('ALTER TABLE article_category DROP FOREIGN KEY FK_53A4EDAA12469DE2');
        $this->addSql('ALTER TABLE page_tile DROP FOREIGN KEY FK_F14BB5DEC4663E4');
        $this->addSql('ALTER TABLE page_tile DROP FOREIGN KEY FK_F14BB5DE638AF48B');
        $this->addSql('DROP TABLE article_category');
        $this->addSql('DROP TABLE page_tile');
        $this->addSql('ALTER TABLE article DROP FOREIGN KEY FK_23A0E66A76ED395');
        $this->addSql('DROP INDEX IDX_23A0E66A76ED395 ON article');
        $this->addSql('ALTER TABLE article DROP user_id');
        $this->addSql('ALTER TABLE banner_text DROP FOREIGN KEY FK_3332F6ADC4663E4');
        $this->addSql('DROP INDEX IDX_3332F6ADC4663E4 ON banner_text');
        $this->addSql('ALTER TABLE banner_text DROP page_id');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526C7294869C');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526CA76ED395');
        $this->addSql('DROP INDEX IDX_9474526C7294869C ON comment');
        $this->addSql('DROP INDEX IDX_9474526CA76ED395 ON comment');
        $this->addSql('ALTER TABLE comment DROP article_id, DROP user_id');
        $this->addSql('ALTER TABLE keyword DROP FOREIGN KEY FK_5A93713B7294869C');
        $this->addSql('DROP INDEX IDX_5A93713B7294869C ON keyword');
        $this->addSql('ALTER TABLE keyword DROP article_id');
    }
}
