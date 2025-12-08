<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251207232222 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE invoices (id VARBINARY(16) NOT NULL, invoice_number VARCHAR(50) NOT NULL, amount NUMERIC(10, 2) NOT NULL, tax_amount NUMERIC(10, 2) DEFAULT NULL, total_amount NUMERIC(10, 2) NOT NULL, currency VARCHAR(10) NOT NULL, description LONGTEXT DEFAULT NULL, status VARCHAR(255) NOT NULL, issue_date DATE NOT NULL, due_date DATE NOT NULL, paid_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, merchant_id VARBINARY(16) NOT NULL, payment_id VARBINARY(16) DEFAULT NULL, contract_id VARBINARY(16) DEFAULT NULL, UNIQUE INDEX UNIQ_6A2F2F952DA68207 (invoice_number), INDEX IDX_6A2F2F956796D554 (merchant_id), INDEX IDX_6A2F2F954C3A3BB (payment_id), INDEX IDX_6A2F2F952576E0FD (contract_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE invoices ADD CONSTRAINT FK_6A2F2F956796D554 FOREIGN KEY (merchant_id) REFERENCES merchants (id)');
        $this->addSql('ALTER TABLE invoices ADD CONSTRAINT FK_6A2F2F954C3A3BB FOREIGN KEY (payment_id) REFERENCES payments (id)');
        $this->addSql('ALTER TABLE invoices ADD CONSTRAINT FK_6A2F2F952576E0FD FOREIGN KEY (contract_id) REFERENCES contracts (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE invoices DROP FOREIGN KEY FK_6A2F2F956796D554');
        $this->addSql('ALTER TABLE invoices DROP FOREIGN KEY FK_6A2F2F954C3A3BB');
        $this->addSql('ALTER TABLE invoices DROP FOREIGN KEY FK_6A2F2F952576E0FD');
        $this->addSql('DROP TABLE invoices');
    }
}
