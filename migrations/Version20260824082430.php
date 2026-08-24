<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260824082430 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Убирает внешние ссылки из разделов, принимающих только PDF-файлы';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE section_document SET external_url = NULL WHERE section IN ('guest-info', 'transfer', 'public-transport', 'prices')");
    }

    public function down(Schema $schema): void
    {
    }
}
