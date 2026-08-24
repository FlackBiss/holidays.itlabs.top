<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260824082440 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Переименовывает раздел времени работы служб в «Контакты»';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE content_page SET title = 'Контакты' WHERE type = 'service_hours'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE content_page SET title = 'Время работы служб' WHERE type = 'service_hours'");
    }
}
