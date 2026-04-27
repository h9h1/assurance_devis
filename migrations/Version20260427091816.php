<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260427091816 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cities CHANGE is_active is_active TINYINT NOT NULL');
        $this->addSql('ALTER TABLE cities RENAME INDEX uniq_d95db6d25e237e06 TO UNIQ_D95DB16B5E237E06');
        $this->addSql('ALTER TABLE companies CHANGE is_active is_active TINYINT NOT NULL');
        $this->addSql('ALTER TABLE offers ADD annualPrice NUMERIC(12, 2) NOT NULL, ADD monthlyPrice NUMERIC(12, 2) NOT NULL, DROP annual_price, DROP monthly_price');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cities CHANGE is_active is_active TINYINT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE cities RENAME INDEX uniq_d95db16b5e237e06 TO UNIQ_D95DB6D25E237E06');
        $this->addSql('ALTER TABLE companies CHANGE is_active is_active TINYINT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE offers ADD annual_price NUMERIC(12, 2) NOT NULL, ADD monthly_price NUMERIC(12, 2) NOT NULL, DROP annualPrice, DROP monthlyPrice');
    }
}
