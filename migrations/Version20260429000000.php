<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260429000000 extends AbstractMigration
{
    public function getDescription(): string { return 'Create admin_users table'; }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE admin_users (
                id         INT AUTO_INCREMENT NOT NULL,
                email      VARCHAR(180) NOT NULL,
                name       VARCHAR(100) NOT NULL,
                roles      JSON NOT NULL,
                password   VARCHAR(255) NOT NULL,
                is_active  TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                UNIQUE KEY uniq_admin_email (email),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ');
    }

    public function down(Schema $schema): void { $this->addSql('DROP TABLE admin_users'); }
}
