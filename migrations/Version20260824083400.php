<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260824083400 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Разделяет расписание обеденных залов по основной территории и корпусу 7';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE content_page SET data = JSON_REMOVE(JSON_SET(data, '$.mainTerritoryDiningHallDescription', COALESCE(JSON_UNQUOTE(JSON_EXTRACT(data, '$.diningHallsDescription')), 'Обеденный зал основной территории — расписание уточняется.'), '$.buildingSevenDiningHallDescription', COALESCE(JSON_UNQUOTE(JSON_EXTRACT(data, '$.diningHallsDescription')), 'Обеденный зал территории корпуса 7 — расписание уточняется.')), '$.diningHallsDescription') WHERE type = 'meal_times'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE content_page SET data = JSON_REMOVE(JSON_SET(data, '$.diningHallsDescription', COALESCE(JSON_UNQUOTE(JSON_EXTRACT(data, '$.mainTerritoryDiningHallDescription')), '')), '$.mainTerritoryDiningHallDescription', '$.buildingSevenDiningHallDescription') WHERE type = 'meal_times'");
    }
}
