<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260427120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is_active to offers; add city_entity_id and company_entity_id FK to quotes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE offers ADD is_active TINYINT(1) NOT NULL DEFAULT 1');

        $this->addSql('ALTER TABLE quotes ADD city_entity_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE quotes ADD CONSTRAINT FK_quotes_city FOREIGN KEY (city_entity_id) REFERENCES cities (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_quotes_city ON quotes (city_entity_id)');

        $this->addSql('ALTER TABLE quotes ADD company_entity_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE quotes ADD CONSTRAINT FK_quotes_company FOREIGN KEY (company_entity_id) REFERENCES companies (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_quotes_company ON quotes (company_entity_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE offers DROP is_active');
        $this->addSql('ALTER TABLE quotes DROP FOREIGN KEY FK_quotes_city');
        $this->addSql('DROP INDEX IDX_quotes_city ON quotes');
        $this->addSql('ALTER TABLE quotes DROP city_entity_id');
        $this->addSql('ALTER TABLE quotes DROP FOREIGN KEY FK_quotes_company');
        $this->addSql('DROP INDEX IDX_quotes_company ON quotes');
        $this->addSql('ALTER TABLE quotes DROP company_entity_id');
    }
}
