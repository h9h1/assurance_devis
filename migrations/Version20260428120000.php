<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260428120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migrate DRAFT quotes to CONFIRMED status';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE quotes SET status = 'confirmed' WHERE status = 'draft'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE quotes SET status = 'draft' WHERE status = 'confirmed'");
    }
}
