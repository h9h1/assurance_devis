<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260428000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create company_offer_variations table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE company_offer_variations (
                id             INT AUTO_INCREMENT NOT NULL,
                company_id     INT NOT NULL,
                offer_id       INT NOT NULL,
                variation_type VARCHAR(10) NOT NULL DEFAULT \'percent\',
                value          DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                is_active      TINYINT(1) NOT NULL DEFAULT 1,
                created_at     DATETIME NOT NULL,
                updated_at     DATETIME NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uq_company_offer (company_id, offer_id),
                CONSTRAINT FK_cov_company FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE,
                CONSTRAINT FK_cov_offer   FOREIGN KEY (offer_id)   REFERENCES offers   (id) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE company_offer_variations');
    }
}
