<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251207223119 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE payment_reminders (id VARBINARY(16) NOT NULL, type VARCHAR(20) NOT NULL, message LONGTEXT NOT NULL, scheduled_at DATETIME NOT NULL, sent_at DATETIME DEFAULT NULL, is_sent TINYINT NOT NULL, error_message LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, payment_id VARBINARY(16) NOT NULL, merchant_id VARBINARY(16) NOT NULL, INDEX IDX_2EBB96FC4C3A3BB (payment_id), INDEX IDX_2EBB96FC6796D554 (merchant_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE transactions (id VARBINARY(16) NOT NULL, transaction_number VARCHAR(50) NOT NULL, type VARCHAR(255) NOT NULL, method VARCHAR(255) NOT NULL, amount NUMERIC(10, 2) NOT NULL, currency VARCHAR(10) NOT NULL, external_reference VARCHAR(100) DEFAULT NULL, phone_number VARCHAR(50) DEFAULT NULL, description LONGTEXT DEFAULT NULL, metadata JSON DEFAULT NULL, status VARCHAR(20) NOT NULL, error_message LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, completed_at DATETIME DEFAULT NULL, payment_id VARBINARY(16) NOT NULL, merchant_id VARBINARY(16) NOT NULL, UNIQUE INDEX UNIQ_EAA81A4CE0ED6D14 (transaction_number), INDEX IDX_EAA81A4C4C3A3BB (payment_id), INDEX IDX_EAA81A4C6796D554 (merchant_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE payment_reminders ADD CONSTRAINT FK_2EBB96FC4C3A3BB FOREIGN KEY (payment_id) REFERENCES payments (id)');
        $this->addSql('ALTER TABLE payment_reminders ADD CONSTRAINT FK_2EBB96FC6796D554 FOREIGN KEY (merchant_id) REFERENCES merchants (id)');
        $this->addSql('ALTER TABLE transactions ADD CONSTRAINT FK_EAA81A4C4C3A3BB FOREIGN KEY (payment_id) REFERENCES payments (id)');
        $this->addSql('ALTER TABLE transactions ADD CONSTRAINT FK_EAA81A4C6796D554 FOREIGN KEY (merchant_id) REFERENCES merchants (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE payment_reminders DROP FOREIGN KEY FK_2EBB96FC4C3A3BB');
        $this->addSql('ALTER TABLE payment_reminders DROP FOREIGN KEY FK_2EBB96FC6796D554');
        $this->addSql('ALTER TABLE transactions DROP FOREIGN KEY FK_EAA81A4C4C3A3BB');
        $this->addSql('ALTER TABLE transactions DROP FOREIGN KEY FK_EAA81A4C6796D554');
        $this->addSql('DROP TABLE payment_reminders');
        $this->addSql('DROP TABLE transactions');
    }
}
