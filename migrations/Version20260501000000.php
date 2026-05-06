<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260501000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add uuid and access_token columns to quotes table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE quotes ADD uuid CHAR(36) NULL AFTER id, ADD access_token VARCHAR(64) NULL AFTER uuid');
        $this->addSql("UPDATE quotes SET uuid = UUID(), access_token = SHA2(CONCAT(id, UUID(), RAND()), 256) WHERE uuid IS NULL");
        $this->addSql('ALTER TABLE quotes MODIFY uuid CHAR(36) NOT NULL, MODIFY access_token VARCHAR(64) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX uniq_quote_uuid ON quotes (uuid)');
        $this->addSql('CREATE UNIQUE INDEX uniq_quote_access_token ON quotes (access_token)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX uniq_quote_uuid ON quotes');
        $this->addSql('DROP INDEX uniq_quote_access_token ON quotes');
        $this->addSql('ALTER TABLE quotes DROP COLUMN uuid, DROP COLUMN access_token');
    }
}
