<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260423201234 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE quotes ADD lastName VARCHAR(100) NOT NULL, ADD firstName VARCHAR(100) NOT NULL, ADD phoneNumber VARCHAR(20) NOT NULL, ADD birthDate DATE NOT NULL, ADD licenseDate DATE NOT NULL, ADD insuranceType VARCHAR(255) NOT NULL, ADD fuelType VARCHAR(255) NOT NULL, ADD firstRegistrationDate DATE NOT NULL, ADD newValue NUMERIC(12, 2) NOT NULL, ADD marketValue NUMERIC(12, 2) NOT NULL, ADD registrationNumber VARCHAR(20) NOT NULL, ADD fiscalPower INT DEFAULT NULL, ADD engineCapacity INT DEFAULT NULL, ADD createdAt DATETIME NOT NULL, ADD updatedAt DATETIME NOT NULL, DROP last_name, DROP first_name, DROP phone_number, DROP birth_date, DROP license_date, DROP insurance_type, DROP fuel_type, DROP first_registration_date, DROP new_value, DROP market_value, DROP registration_number, DROP fiscal_power, DROP engine_capacity, DROP created_at, DROP updated_at, CHANGE vehicle_brand vehicleBrand VARCHAR(120) NOT NULL, CHANGE seat_count seatCount INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE quotes ADD last_name VARCHAR(100) NOT NULL, ADD first_name VARCHAR(100) NOT NULL, ADD phone_number VARCHAR(20) NOT NULL, ADD birth_date DATE NOT NULL, ADD license_date DATE NOT NULL, ADD insurance_type VARCHAR(255) NOT NULL, ADD fuel_type VARCHAR(255) NOT NULL, ADD first_registration_date DATE NOT NULL, ADD new_value NUMERIC(12, 2) NOT NULL, ADD market_value NUMERIC(12, 2) NOT NULL, ADD registration_number VARCHAR(20) NOT NULL, ADD fiscal_power INT DEFAULT NULL, ADD engine_capacity INT DEFAULT NULL, ADD created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP lastName, DROP firstName, DROP phoneNumber, DROP birthDate, DROP licenseDate, DROP insuranceType, DROP fuelType, DROP firstRegistrationDate, DROP newValue, DROP marketValue, DROP registrationNumber, DROP fiscalPower, DROP engineCapacity, DROP createdAt, DROP updatedAt, CHANGE vehicleBrand vehicle_brand VARCHAR(120) NOT NULL, CHANGE seatCount seat_count INT NOT NULL');
    }
}
