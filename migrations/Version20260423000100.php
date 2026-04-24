<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260423000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create quotes table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE quotes (
                id INT AUTO_INCREMENT NOT NULL,
                last_name VARCHAR(100) NOT NULL,
                first_name VARCHAR(100) NOT NULL,
                city VARCHAR(255) NOT NULL,
                phone_number VARCHAR(20) NOT NULL,
                birth_date DATE NOT NULL,
                license_date DATE NOT NULL,
                insurance_type VARCHAR(255) NOT NULL,
                vehicle_brand VARCHAR(120) NOT NULL,
                fuel_type VARCHAR(255) NOT NULL,
                first_registration_date DATE NOT NULL,
                seat_count INT NOT NULL,
                new_value NUMERIC(12, 2) NOT NULL,
                market_value NUMERIC(12, 2) NOT NULL,
                registration_number VARCHAR(20) NOT NULL,
                fiscal_power INT DEFAULT NULL,
                engine_capacity INT DEFAULT NULL,
                status VARCHAR(255) NOT NULL,
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE quotes');
    }
}
