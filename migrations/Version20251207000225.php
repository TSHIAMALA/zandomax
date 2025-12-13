<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251207000225 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE currency (id VARBINARY(16) NOT NULL, code VARCHAR(3) NOT NULL, label VARCHAR(50) NOT NULL, symbol VARCHAR(10) NOT NULL, is_deleted TINYINT NOT NULL, UNIQUE INDEX UNIQ_6956883F77153098 (code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE pricing_rule (id VARBINARY(16) NOT NULL, periodicity VARCHAR(50) NOT NULL, price NUMERIC(12, 2) NOT NULL, is_deleted TINYINT NOT NULL, is_active TINYINT NOT NULL, min_duration INT NOT NULL, max_duration INT DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, space_id VARBINARY(16) NOT NULL, currency_id VARBINARY(16) DEFAULT NULL, INDEX IDX_6DCEA67223575340 (space_id), INDEX IDX_6DCEA67238248176 (currency_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE space_reservation (id VARBINARY(16) NOT NULL, periodicity VARCHAR(50) NOT NULL, duration INT NOT NULL, first_payment_amount NUMERIC(12, 2) DEFAULT NULL, is_deleted TINYINT NOT NULL, status VARCHAR(50) NOT NULL, rejection_reason LONGTEXT DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, merchant_id VARBINARY(16) NOT NULL, space_id VARBINARY(16) NOT NULL, currency_id VARBINARY(16) DEFAULT NULL, INDEX IDX_5EA2B7236796D554 (merchant_id), INDEX IDX_5EA2B72323575340 (space_id), INDEX IDX_5EA2B72338248176 (currency_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE space_type (id VARBINARY(16) NOT NULL, code VARCHAR(50) NOT NULL, label VARCHAR(150) NOT NULL, description LONGTEXT DEFAULT NULL, is_deleted TINYINT NOT NULL, UNIQUE INDEX UNIQ_E7A82DA377153098 (code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE pricing_rule ADD CONSTRAINT FK_6DCEA67223575340 FOREIGN KEY (space_id) REFERENCES spaces (id)');
        $this->addSql('ALTER TABLE pricing_rule ADD CONSTRAINT FK_6DCEA67238248176 FOREIGN KEY (currency_id) REFERENCES currency (id)');
        $this->addSql('ALTER TABLE space_reservation ADD CONSTRAINT FK_5EA2B7236796D554 FOREIGN KEY (merchant_id) REFERENCES merchants (id)');
        $this->addSql('ALTER TABLE space_reservation ADD CONSTRAINT FK_5EA2B72323575340 FOREIGN KEY (space_id) REFERENCES spaces (id)');
        $this->addSql('ALTER TABLE space_reservation ADD CONSTRAINT FK_5EA2B72338248176 FOREIGN KEY (currency_id) REFERENCES currency (id)');
        $this->addSql('ALTER TABLE merchants ADD person_type VARCHAR(50) DEFAULT NULL, ADD documents JSON DEFAULT NULL, ADD is_deleted TINYINT NOT NULL');
        $this->addSql('ALTER TABLE spaces ADD is_deleted TINYINT NOT NULL, ADD space_type_id VARBINARY(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE spaces ADD CONSTRAINT FK_DD2B6478455857DB FOREIGN KEY (space_type_id) REFERENCES space_type (id)');
        $this->addSql('CREATE INDEX IDX_DD2B6478455857DB ON spaces (space_type_id)');
        $this->addSql('ALTER TABLE users ADD is_deleted TINYINT NOT NULL, ADD last_login DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE pricing_rule DROP FOREIGN KEY FK_6DCEA67223575340');
        $this->addSql('ALTER TABLE pricing_rule DROP FOREIGN KEY FK_6DCEA67238248176');
        $this->addSql('ALTER TABLE space_reservation DROP FOREIGN KEY FK_5EA2B7236796D554');
        $this->addSql('ALTER TABLE space_reservation DROP FOREIGN KEY FK_5EA2B72323575340');
        $this->addSql('ALTER TABLE space_reservation DROP FOREIGN KEY FK_5EA2B72338248176');
        $this->addSql('DROP TABLE currency');
        $this->addSql('DROP TABLE pricing_rule');
        $this->addSql('DROP TABLE space_reservation');
        $this->addSql('DROP TABLE space_type');
        $this->addSql('ALTER TABLE merchants DROP person_type, DROP documents, DROP is_deleted');
        $this->addSql('ALTER TABLE spaces DROP FOREIGN KEY FK_DD2B6478455857DB');
        $this->addSql('DROP INDEX IDX_DD2B6478455857DB ON spaces');
        $this->addSql('ALTER TABLE spaces DROP is_deleted, DROP space_type_id');
        $this->addSql('ALTER TABLE users DROP is_deleted, DROP last_login');
    }
}
