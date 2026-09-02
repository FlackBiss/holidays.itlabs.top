<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260825180500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Aligns general settings column defaults with the Doctrine mapping';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE site_settings CHANGE modal_timeout_seconds modal_timeout_seconds INT NOT NULL, CHANGE slide_duration_seconds slide_duration_seconds INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE site_settings CHANGE modal_timeout_seconds modal_timeout_seconds INT DEFAULT 60 NOT NULL, CHANGE slide_duration_seconds slide_duration_seconds INT DEFAULT 10 NOT NULL');
    }
}
