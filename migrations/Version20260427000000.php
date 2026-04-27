<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify it manually!
 */
final class Version20260427000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create companies, cities and offers tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE companies (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, is_active TINYINT(1) NOT NULL DEFAULT 1, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_8244AA3A5E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE cities (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, is_active TINYINT(1) NOT NULL DEFAULT 1, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_D95DB6D25E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE offers (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(100) NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, annual_price NUMERIC(12, 2) NOT NULL, monthly_price NUMERIC(12, 2) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Insert default data for companies
        $this->addSql("INSERT INTO companies (name, description, is_active, created_at, updated_at) VALUES ('Axa Assurance', NULL, 1, NOW(), NOW())");
        $this->addSql("INSERT INTO companies (name, description, is_active, created_at, updated_at) VALUES ('Wafa Assurance', NULL, 1, NOW(), NOW())");
        $this->addSql("INSERT INTO companies (name, description, is_active, created_at, updated_at) VALUES ('RMA', NULL, 1, NOW(), NOW())");

        // Insert default cities
        $cities = [
            'Casablanca',
            'Rabat',
            'Salé',
            'Fès',
            'Marrakech',
            'Tanger',
            'Agadir',
            'Meknès',
            'Oujda',
            'Kénitra',
            'Tétouan',
            'Safi',
            'El Jadida',
            'Nador',
            'Laâyoune',
            'Dakhla',
            'Béni Mellal',
            'Khouribga',
            'Taza',
            'Errachidia',
            'Essaouira',
            'Ouarzazate',
            'Guelmim',
            'Ifrane',
            'Chefchaouen',
            'Mohammédia',
            'Larache',
            'Ksar El Kebir',
            'Berkane',
            'Al Hoceïma',
            'Sidi Kacem',
            'Sidi Slimane',
            'Taroudant',
            'Azrou',
            'Tiznit',
            'Midelt',
            'Taourirt',
            'Settat',
            'Temara'
        ];

        foreach ($cities as $city) {
            $this->addSql("INSERT INTO cities (name, description, is_active, created_at, updated_at) VALUES ('" . addslashes($city) . "', NULL, 1, NOW(), NOW())");
        }

        // Insert default offers
        $this->addSql("INSERT INTO offers (code, title, description, annual_price, monthly_price, created_at, updated_at) VALUES ('tiers', 'Tiers', 'Responsabilité civile et défense', 2500.00, 208.33, NOW(), NOW())");
        $this->addSql("INSERT INTO offers (code, title, description, annual_price, monthly_price, created_at, updated_at) VALUES ('intermediaire', 'Intermédiaire', 'Responsabilité civile, vol, incendie, bris de glace', 5000.00, 416.67, NOW(), NOW())");
        $this->addSql("INSERT INTO offers (code, title, description, annual_price, monthly_price, created_at, updated_at) VALUES ('tous_risques', 'Tous risques', 'Couverture complète avec assistance', 8000.00, 666.67, NOW(), NOW())");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE companies');
        $this->addSql('DROP TABLE cities');
        $this->addSql('DROP TABLE offers');
    }
}
