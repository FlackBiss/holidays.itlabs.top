<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260902120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds allowed links to general site settings';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE site_settings ADD allowed_links JSON DEFAULT NULL');
        $this->addSql("UPDATE site_settings SET allowed_links = '[]' WHERE allowed_links IS NULL");
        $this->addSql('ALTER TABLE site_settings CHANGE allowed_links allowed_links JSON NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE site_settings DROP allowed_links');
    }
}
