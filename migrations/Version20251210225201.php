<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251210225201 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make supplier_id column nullable in product table.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        // Ensure the column exists before modifying (fresh databases may not have it yet)
        $this->addSql('ALTER TABLE product ADD COLUMN IF NOT EXISTS supplier_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE product MODIFY supplier_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        // Revert to NOT NULL only if the column exists
        $this->addSql('ALTER TABLE product MODIFY supplier_id INT NOT NULL');
    }
}
