<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260505081943 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE quotes RENAME INDEX uniq_quote_uuid TO UNIQ_A1B588C5D17F50A6');
        $this->addSql('ALTER TABLE quotes RENAME INDEX uniq_quote_access_token TO UNIQ_A1B588C5B6A2DD68');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE quotes RENAME INDEX uniq_a1b588c5b6a2dd68 TO uniq_quote_access_token');
        $this->addSql('ALTER TABLE quotes RENAME INDEX uniq_a1b588c5d17f50a6 TO uniq_quote_uuid');
    }
}
