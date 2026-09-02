<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260825161500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds the modal appearance timeout to the general site settings';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE site_settings ADD modal_timeout_seconds INT DEFAULT 60 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE site_settings DROP modal_timeout_seconds');
    }
}
