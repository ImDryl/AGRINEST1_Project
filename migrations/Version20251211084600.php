<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251211084600 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Set invalid supplier_id values to NULL before adding foreign key
        $this->addSql('UPDATE product SET supplier_id = NULL WHERE supplier_id IS NOT NULL AND supplier_id NOT IN (SELECT id FROM supplier)');
        
        // Drop existing foreign key if it exists
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY IF EXISTS FK_D34A04ADB03A8386');
        
        // Add supplier foreign key constraint (nullable)
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD2ADD6D8C FOREIGN KEY (supplier_id) REFERENCES supplier (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_D34A04AD2ADD6D8C ON product (supplier_id)');
        
        // Re-add created_by_id foreign key constraint
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04ADB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD2ADD6D8C');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04ADB03A8386');
        $this->addSql('DROP INDEX IDX_D34A04AD2ADD6D8C ON product');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04ADB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE SET NULL');
    }
}
