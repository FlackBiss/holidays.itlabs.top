<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260824082420 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Удаляет анимационную программу и описания у маршрутов общественного транспорта и прайсов';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("DELETE FROM section_document WHERE section = 'animation'");
        $this->addSql("DELETE FROM content_item WHERE section = 'infrastructure' AND title = 'Анимационные программы'");
        $this->addSql("UPDATE section_document SET description = NULL WHERE section IN ('guest-info', 'public-transport', 'prices')");
        $this->addSql("UPDATE section_document SET external_url = NULL WHERE section = 'guest-info'");
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Удалённые пользовательские файлы и описания автоматически восстановить нельзя.');
    }
}
