<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251215073939 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE IF NOT EXISTS activity_log (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, username VARCHAR(255) NOT NULL, role VARCHAR(50) NOT NULL, action VARCHAR(50) NOT NULL, entity_type VARCHAR(100) DEFAULT NULL, entity_id INT DEFAULT NULL, details LONGTEXT DEFAULT NULL, timestamp DATETIME NOT NULL, INDEX idx_user (user_id), INDEX idx_action (action), INDEX idx_entity_type (entity_type), INDEX idx_timestamp (timestamp), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS `order` (id INT AUTO_INCREMENT NOT NULL, created_by_id INT DEFAULT NULL, order_number VARCHAR(255) NOT NULL, customer_name VARCHAR(255) NOT NULL, customer_email VARCHAR(255) NOT NULL, customer_phone VARCHAR(255) NOT NULL, order_date DATETIME NOT NULL, status VARCHAR(50) NOT NULL, total NUMERIC(10, 2) NOT NULL, UNIQUE INDEX UNIQ_F5299398551F0F81 (order_number), INDEX IDX_F5299398B03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS order_item (id INT AUTO_INCREMENT NOT NULL, order_id INT NOT NULL, product_id INT NOT NULL, quantity INT NOT NULL, price NUMERIC(10, 2) NOT NULL, INDEX IDX_52EA1F098D9F6D38 (order_id), INDEX IDX_52EA1F094584665A (product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE activity_log DROP FOREIGN KEY IF EXISTS FK_FD06F647A76ED395');
        $this->addSql('ALTER TABLE activity_log ADD CONSTRAINT FK_FD06F647A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY IF EXISTS FK_F5299398B03A8386');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F5299398B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE order_item DROP FOREIGN KEY IF EXISTS FK_52EA1F098D9F6D38');
        $this->addSql('ALTER TABLE order_item ADD CONSTRAINT FK_52EA1F098D9F6D38 FOREIGN KEY (order_id) REFERENCES `order` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE order_item DROP FOREIGN KEY IF EXISTS FK_52EA1F094584665A');
        $this->addSql('ALTER TABLE order_item ADD CONSTRAINT FK_52EA1F094584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE category ADD COLUMN IF NOT EXISTS created_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE category DROP FOREIGN KEY IF EXISTS FK_64C19C1B03A8386');
        $this->addSql('ALTER TABLE category ADD CONSTRAINT FK_64C19C1B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_64C19C1B03A8386 ON category (created_by_id)');
        $this->addSql('ALTER TABLE product ADD COLUMN IF NOT EXISTS supplier_id INT DEFAULT NULL, ADD COLUMN IF NOT EXISTS created_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY IF EXISTS FK_D34A04AD2ADD6D8C');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY IF EXISTS FK_D34A04ADB03A8386');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD2ADD6D8C FOREIGN KEY (supplier_id) REFERENCES supplier (id)');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04ADB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_D34A04AD2ADD6D8C ON product (supplier_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_D34A04ADB03A8386 ON product (created_by_id)');
        $this->addSql('ALTER TABLE supplier ADD COLUMN IF NOT EXISTS created_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE supplier DROP FOREIGN KEY IF EXISTS FK_9B2A6C7EB03A8386');
        $this->addSql('ALTER TABLE supplier ADD CONSTRAINT FK_9B2A6C7EB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_9B2A6C7EB03A8386 ON supplier (created_by_id)');
        $this->addSql('ALTER TABLE user ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1 NOT NULL, ADD COLUMN IF NOT EXISTS created_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE activity_log DROP FOREIGN KEY FK_FD06F647A76ED395');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F5299398B03A8386');
        $this->addSql('ALTER TABLE order_item DROP FOREIGN KEY FK_52EA1F098D9F6D38');
        $this->addSql('ALTER TABLE order_item DROP FOREIGN KEY FK_52EA1F094584665A');
        $this->addSql('DROP TABLE activity_log');
        $this->addSql('DROP TABLE `order`');
        $this->addSql('DROP TABLE order_item');
        $this->addSql('ALTER TABLE category DROP FOREIGN KEY FK_64C19C1B03A8386');
        $this->addSql('DROP INDEX IDX_64C19C1B03A8386 ON category');
        $this->addSql('ALTER TABLE category DROP created_by_id');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD2ADD6D8C');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04ADB03A8386');
        $this->addSql('DROP INDEX IDX_D34A04AD2ADD6D8C ON product');
        $this->addSql('DROP INDEX IDX_D34A04ADB03A8386 ON product');
        $this->addSql('ALTER TABLE product DROP supplier_id, DROP created_by_id');
        $this->addSql('ALTER TABLE supplier DROP FOREIGN KEY FK_9B2A6C7EB03A8386');
        $this->addSql('DROP INDEX IDX_9B2A6C7EB03A8386 ON supplier');
        $this->addSql('ALTER TABLE supplier DROP created_by_id');
        $this->addSql('ALTER TABLE user DROP is_active, DROP created_at');
    }
}
