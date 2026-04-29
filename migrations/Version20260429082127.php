<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260429082127 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE admin_users CHANGE is_active is_active TINYINT NOT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE company_offer_variations CHANGE variation_type variation_type VARCHAR(10) NOT NULL, CHANGE value value NUMERIC(10, 2) NOT NULL, CHANGE is_active is_active TINYINT NOT NULL');
        $this->addSql('ALTER TABLE offers CHANGE is_active is_active TINYINT NOT NULL');
        $this->addSql('ALTER TABLE quotes DROP FOREIGN KEY `FK_quotes_city`');
        $this->addSql('ALTER TABLE quotes DROP FOREIGN KEY `FK_quotes_company`');
        $this->addSql('ALTER TABLE quotes CHANGE city city VARCHAR(255) DEFAULT NULL, CHANGE company company VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE quotes ADD CONSTRAINT FK_A1B588C525716148 FOREIGN KEY (city_entity_id) REFERENCES cities (id)');
        $this->addSql('ALTER TABLE quotes ADD CONSTRAINT FK_A1B588C5B23EF7AD FOREIGN KEY (company_entity_id) REFERENCES companies (id)');
        $this->addSql('ALTER TABLE quotes RENAME INDEX idx_quotes_city TO IDX_A1B588C525716148');
        $this->addSql('ALTER TABLE quotes RENAME INDEX idx_quotes_company TO IDX_A1B588C5B23EF7AD');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE admin_users CHANGE is_active is_active TINYINT DEFAULT 1 NOT NULL, CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE company_offer_variations CHANGE variation_type variation_type VARCHAR(10) DEFAULT \'percent\' NOT NULL, CHANGE value value NUMERIC(10, 2) DEFAULT \'0.00\' NOT NULL, CHANGE is_active is_active TINYINT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE offers CHANGE is_active is_active TINYINT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE quotes DROP FOREIGN KEY FK_A1B588C525716148');
        $this->addSql('ALTER TABLE quotes DROP FOREIGN KEY FK_A1B588C5B23EF7AD');
        $this->addSql('ALTER TABLE quotes CHANGE city city VARCHAR(255) NOT NULL, CHANGE company company VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE quotes ADD CONSTRAINT `FK_quotes_city` FOREIGN KEY (city_entity_id) REFERENCES cities (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE quotes ADD CONSTRAINT `FK_quotes_company` FOREIGN KEY (company_entity_id) REFERENCES companies (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE quotes RENAME INDEX idx_a1b588c525716148 TO IDX_quotes_city');
        $this->addSql('ALTER TABLE quotes RENAME INDEX idx_a1b588c5b23ef7ad TO IDX_quotes_company');
    }
}
