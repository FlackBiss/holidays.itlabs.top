<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260824082900 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Убирает PDF программы Подключайся в пользу структурированной страницы';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE content_page SET document_file_name = NULL, document_updated_at = NULL WHERE type = 'connect'");
    }

    public function down(Schema $schema): void
    {
    }
}
