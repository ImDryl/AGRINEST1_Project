<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251211100631 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE category ADD created_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE category ADD CONSTRAINT FK_64C19C1B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_64C19C1B03A8386 ON category (created_by_id)');
        $this->addSql('ALTER TABLE supplier ADD created_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE supplier ADD CONSTRAINT FK_9B2A6C7EB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_9B2A6C7EB03A8386 ON supplier (created_by_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE category DROP FOREIGN KEY FK_64C19C1B03A8386');
        $this->addSql('DROP INDEX IDX_64C19C1B03A8386 ON category');
        $this->addSql('ALTER TABLE category DROP created_by_id');
        $this->addSql('ALTER TABLE supplier DROP FOREIGN KEY FK_9B2A6C7EB03A8386');
        $this->addSql('DROP INDEX IDX_9B2A6C7EB03A8386 ON supplier');
        $this->addSql('ALTER TABLE supplier DROP created_by_id');
    }
}
