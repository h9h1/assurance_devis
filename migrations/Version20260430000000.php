<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260430100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename camelCase columns to snake_case in quotes table (adminNote, customEstimation, selectedOffer)';
    }

    public function up(Schema $schema): void
    {
        // Renommer les colonnes camelCase restantes en snake_case
        $this->addSql('ALTER TABLE quotes
            CHANGE adminNote      admin_note        LONGTEXT DEFAULT NULL,
            CHANGE customEstimation custom_estimation NUMERIC(12,2) DEFAULT NULL,
            CHANGE selectedOffer  selected_offer    VARCHAR(100) DEFAULT NULL
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE quotes
            CHANGE admin_note         adminNote        LONGTEXT DEFAULT NULL,
            CHANGE custom_estimation  customEstimation NUMERIC(12,2) DEFAULT NULL,
            CHANGE selected_offer     selectedOffer    VARCHAR(100) DEFAULT NULL
        ');
    }
}