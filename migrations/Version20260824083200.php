<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260824083200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Редактируемые расписания общественного транспорта и трансфера';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("DELETE FROM section_document WHERE section IN ('public-transport', 'transfer')");
        $this->addSql("INSERT INTO content_page (type, title, description, data, active) SELECT 'transfer', 'Трансфер между территориями', NULL, '{\"mainTerritoryDepartureTimes\":[],\"buildingSevenDepartureTimes\":[]}', 1 WHERE NOT EXISTS (SELECT 1 FROM content_page WHERE type = 'transfer')");
        $this->addSql('CREATE TABLE public_transport_route (id INT AUTO_INCREMENT NOT NULL, route_number VARCHAR(32) NOT NULL, file_name VARCHAR(255) DEFAULT NULL, updated_at DATETIME DEFAULT NULL, schedules JSON NOT NULL, priority INT NOT NULL, active TINYINT NOT NULL, UNIQUE INDEX uniq_public_transport_route_number (route_number), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE public_transport_route');
        $this->addSql("DELETE FROM content_page WHERE type = 'transfer'");
    }
}
