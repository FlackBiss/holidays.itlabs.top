<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260824082700 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Добавляет категории легенды и признак расчерченного маршрута объектов';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE map_place ADD category VARCHAR(255) DEFAULT NULL, ADD route_drawn TINYINT DEFAULT NULL');
        $this->addSql("UPDATE map_place SET category = CASE WHEN type = 'residential' THEN 'residential' WHEN LOWER(name) LIKE '%спорт%' OR LOWER(name) LIKE '%бассейн%' THEN 'sport' ELSE 'buildings' END, route_drawn = 1");
        $this->addSql('ALTER TABLE map_place CHANGE category category VARCHAR(255) NOT NULL, CHANGE route_drawn route_drawn TINYINT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE map_place DROP category, DROP route_drawn');
    }
}
