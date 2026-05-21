<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Payment\OrderPaymentMethods;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260521120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add payment_method column to order table and backfill from legacy status values';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `order` ADD payment_method VARCHAR(50) DEFAULT NULL');
    }

    public function postUp(Schema $schema): void
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT id, status FROM `order` WHERE status IS NOT NULL'
        );

        foreach ($rows as $row) {
            $raw = (string) $row['status'];
            [$status, $payment] = OrderPaymentMethods::parseLegacyStatus($raw);
            if ($payment === '') {
                continue;
            }

            $this->connection->executeStatement(
                'UPDATE `order` SET status = :status, payment_method = :payment WHERE id = :id',
                [
                    'status' => $status !== '' ? $status : 'Pending',
                    'payment' => $payment,
                    'id' => $row['id'],
                ]
            );
        }
    }

    public function down(Schema $schema): void
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT id, status, payment_method FROM `order` WHERE payment_method IS NOT NULL AND payment_method != \'\''
        );

        foreach ($rows as $row) {
            $status = trim((string) $row['status']);
            $payment = trim((string) $row['payment_method']);
            $combined = sprintf('%s · %s', $status !== '' ? $status : 'Pending', $payment);
            if (strlen($combined) > 50) {
                $combined = substr($combined, 0, 50);
            }

            $this->connection->executeStatement(
                'UPDATE `order` SET status = :status WHERE id = :id',
                ['status' => $combined, 'id' => $row['id']]
            );
        }

        $this->addSql('ALTER TABLE `order` DROP payment_method');
    }
}
