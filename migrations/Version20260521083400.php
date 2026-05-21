<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260521083400 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE stock_log (id INT AUTO_INCREMENT NOT NULL, product_id INT DEFAULT NULL, user_id INT DEFAULT NULL, product_name VARCHAR(255) NOT NULL, username VARCHAR(255) NOT NULL, role VARCHAR(50) NOT NULL, change_type VARCHAR(50) NOT NULL, previous_quantity INT NOT NULL, new_quantity INT NOT NULL, quantity_change INT NOT NULL, note LONGTEXT DEFAULT NULL, reference_id INT DEFAULT NULL, created_at DATETIME NOT NULL, INDEX IDX_F951DD53A76ED395 (user_id), INDEX idx_stock_log_product (product_id), INDEX idx_stock_log_change_type (change_type), INDEX idx_stock_log_created_at (created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE stock_log ADD CONSTRAINT FK_F951DD534584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE stock_log ADD CONSTRAINT FK_F951DD53A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE stock_log DROP FOREIGN KEY FK_F951DD534584665A');
        $this->addSql('ALTER TABLE stock_log DROP FOREIGN KEY FK_F951DD53A76ED395');
        $this->addSql('DROP TABLE stock_log');
    }
}
