<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260824082450 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Удаляет устаревшие текстовые списки инфраструктуры по территориям';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("DELETE FROM content_item WHERE section = 'infrastructure'");
    }

    public function down(Schema $schema): void
    {
    }
}
