<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260824083300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Удаляет повторно созданные устаревшие документы транспортных разделов';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("DELETE FROM section_document WHERE section IN ('public-transport', 'transfer')");
    }

    public function down(Schema $schema): void
    {
    }
}
