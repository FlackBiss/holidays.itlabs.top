<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260824083000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Заменяет файл инфраструктуры двумя редактируемыми текстовыми списками';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("DELETE FROM section_document WHERE section = 'infrastructure'");
        $this->addSql("INSERT INTO content_page (type, title, description, data, active) SELECT 'infrastructure', 'Инфраструктура', NULL, '{\"mainTerritoryInfrastructure\":[],\"buildingSevenInfrastructure\":[]}', 1 WHERE NOT EXISTS (SELECT 1 FROM content_page WHERE type = 'infrastructure')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM content_page WHERE type = 'infrastructure'");
    }
}
