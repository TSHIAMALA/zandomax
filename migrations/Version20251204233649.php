<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251204233649 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE audit_logs (id VARBINARY(16) NOT NULL, actor VARCHAR(100) NOT NULL, action VARCHAR(100) NOT NULL, module VARCHAR(50) NOT NULL, payload JSON DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, INDEX idx_audit_actor (actor), INDEX idx_audit_module (module), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE contracts (id VARBINARY(16) NOT NULL, contract_code VARCHAR(50) NOT NULL, start_date DATE NOT NULL, end_date DATE NOT NULL, rent_amount NUMERIC(10, 2) NOT NULL, guarantee_amount NUMERIC(10, 2) NOT NULL, billing_cycle VARCHAR(50) NOT NULL, status VARCHAR(50) NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, merchant_id VARBINARY(16) NOT NULL, space_id VARBINARY(16) NOT NULL, UNIQUE INDEX UNIQ_950A9731F607EF0 (contract_code), INDEX IDX_950A9736796D554 (merchant_id), INDEX IDX_950A97323575340 (space_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE merchant_category (id VARBINARY(16) NOT NULL, name VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, UNIQUE INDEX UNIQ_61486D785E237E06 (name), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE merchants (id VARBINARY(16) NOT NULL, biometric_hash VARCHAR(255) NOT NULL, firstname VARCHAR(100) NOT NULL, lastname VARCHAR(100) NOT NULL, phone VARCHAR(20) NOT NULL, email VARCHAR(150) DEFAULT NULL, account_number VARCHAR(50) DEFAULT NULL, status VARCHAR(50) NOT NULL, kyc_level VARCHAR(50) NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, merchant_category_id VARBINARY(16) NOT NULL, UNIQUE INDEX UNIQ_CC77B6C05AED90CF (biometric_hash), UNIQUE INDEX UNIQ_CC77B6C0444F97DD (phone), UNIQUE INDEX UNIQ_CC77B6C0E7927C74 (email), INDEX IDX_CC77B6C094F720F1 (merchant_category_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE payments (id VARBINARY(16) NOT NULL, type VARCHAR(50) NOT NULL, amount NUMERIC(10, 2) NOT NULL, currency VARCHAR(3) NOT NULL, due_date DATE DEFAULT NULL, payment_date DATETIME DEFAULT NULL, banking_transaction_id VARCHAR(100) DEFAULT NULL, status VARCHAR(50) NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, merchant_id VARBINARY(16) NOT NULL, contract_id VARBINARY(16) DEFAULT NULL, initiated_by_user_id VARBINARY(16) DEFAULT NULL, processed_by_user_id VARBINARY(16) DEFAULT NULL, INDEX IDX_65D29B326796D554 (merchant_id), INDEX IDX_65D29B322576E0FD (contract_id), INDEX IDX_65D29B322F0C4959 (initiated_by_user_id), INDEX IDX_65D29B32D3492A80 (processed_by_user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE roles (id VARBINARY(16) NOT NULL, code VARCHAR(50) NOT NULL, label VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, UNIQUE INDEX UNIQ_B63E2EC777153098 (code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE space_category (id VARBINARY(16) NOT NULL, name VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, UNIQUE INDEX UNIQ_7ECBC4B85E237E06 (name), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE spaces (id VARBINARY(16) NOT NULL, code VARCHAR(50) NOT NULL, zone VARCHAR(100) NOT NULL, architecture_coord JSON DEFAULT NULL, status VARCHAR(50) NOT NULL, space_category_id VARBINARY(16) NOT NULL, UNIQUE INDEX UNIQ_DD2B647877153098 (code), INDEX IDX_DD2B6478E2C6394 (space_category_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE users (id VARBINARY(16) NOT NULL, email VARCHAR(180) NOT NULL, username VARCHAR(180) NOT NULL, password_hash VARCHAR(255) NOT NULL, enabled TINYINT NOT NULL, merchant_id VARBINARY(16) DEFAULT NULL, UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email), UNIQUE INDEX UNIQ_1483A5E9F85E0677 (username), UNIQUE INDEX UNIQ_1483A5E96796D554 (merchant_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user_roles (user_id VARBINARY(16) NOT NULL, role_id VARBINARY(16) NOT NULL, INDEX IDX_54FCD59FA76ED395 (user_id), INDEX IDX_54FCD59FD60322AC (role_id), PRIMARY KEY (user_id, role_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE videos (id VARBINARY(16) NOT NULL, filepath LONGTEXT NOT NULL, thumbnail LONGTEXT DEFAULT NULL, size_bytes BIGINT DEFAULT NULL, recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, space_id VARBINARY(16) NOT NULL, INDEX IDX_29AA643223575340 (space_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE contracts ADD CONSTRAINT FK_950A9736796D554 FOREIGN KEY (merchant_id) REFERENCES merchants (id)');
        $this->addSql('ALTER TABLE contracts ADD CONSTRAINT FK_950A97323575340 FOREIGN KEY (space_id) REFERENCES spaces (id)');
        $this->addSql('ALTER TABLE merchants ADD CONSTRAINT FK_CC77B6C094F720F1 FOREIGN KEY (merchant_category_id) REFERENCES merchant_category (id)');
        $this->addSql('ALTER TABLE payments ADD CONSTRAINT FK_65D29B326796D554 FOREIGN KEY (merchant_id) REFERENCES merchants (id)');
        $this->addSql('ALTER TABLE payments ADD CONSTRAINT FK_65D29B322576E0FD FOREIGN KEY (contract_id) REFERENCES contracts (id)');
        $this->addSql('ALTER TABLE payments ADD CONSTRAINT FK_65D29B322F0C4959 FOREIGN KEY (initiated_by_user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE payments ADD CONSTRAINT FK_65D29B32D3492A80 FOREIGN KEY (processed_by_user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE spaces ADD CONSTRAINT FK_DD2B6478E2C6394 FOREIGN KEY (space_category_id) REFERENCES space_category (id)');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E96796D554 FOREIGN KEY (merchant_id) REFERENCES merchants (id)');
        $this->addSql('ALTER TABLE user_roles ADD CONSTRAINT FK_54FCD59FA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE user_roles ADD CONSTRAINT FK_54FCD59FD60322AC FOREIGN KEY (role_id) REFERENCES roles (id)');
        $this->addSql('ALTER TABLE videos ADD CONSTRAINT FK_29AA643223575340 FOREIGN KEY (space_id) REFERENCES spaces (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contracts DROP FOREIGN KEY FK_950A9736796D554');
        $this->addSql('ALTER TABLE contracts DROP FOREIGN KEY FK_950A97323575340');
        $this->addSql('ALTER TABLE merchants DROP FOREIGN KEY FK_CC77B6C094F720F1');
        $this->addSql('ALTER TABLE payments DROP FOREIGN KEY FK_65D29B326796D554');
        $this->addSql('ALTER TABLE payments DROP FOREIGN KEY FK_65D29B322576E0FD');
        $this->addSql('ALTER TABLE payments DROP FOREIGN KEY FK_65D29B322F0C4959');
        $this->addSql('ALTER TABLE payments DROP FOREIGN KEY FK_65D29B32D3492A80');
        $this->addSql('ALTER TABLE spaces DROP FOREIGN KEY FK_DD2B6478E2C6394');
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_1483A5E96796D554');
        $this->addSql('ALTER TABLE user_roles DROP FOREIGN KEY FK_54FCD59FA76ED395');
        $this->addSql('ALTER TABLE user_roles DROP FOREIGN KEY FK_54FCD59FD60322AC');
        $this->addSql('ALTER TABLE videos DROP FOREIGN KEY FK_29AA643223575340');
        $this->addSql('DROP TABLE audit_logs');
        $this->addSql('DROP TABLE contracts');
        $this->addSql('DROP TABLE merchant_category');
        $this->addSql('DROP TABLE merchants');
        $this->addSql('DROP TABLE payments');
        $this->addSql('DROP TABLE roles');
        $this->addSql('DROP TABLE space_category');
        $this->addSql('DROP TABLE spaces');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE user_roles');
        $this->addSql('DROP TABLE videos');
    }
}
