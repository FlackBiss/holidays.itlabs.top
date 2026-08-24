<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260824082460 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Удаляет неиспользуемые внешние ссылки у загружаемых документов';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE section_document DROP external_url');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE section_document ADD external_url VARCHAR(2048) DEFAULT NULL');
    }
}
