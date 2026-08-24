<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260824082000 extends AbstractMigration
{
    public function getDescription(): string { return 'Редактор карты: области, точки, дороги, привязки и отдельные медиа-разделы'; }

    public function up(Schema $schema): void
    {
        foreach (['standby_media', 'news_poster', 'animation_poster', 'gallery_media', 'taganrog_media', 'map_icon'] as $table) {
            $this->addSql(sprintf('CREATE TABLE %s (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, file_name VARCHAR(255) DEFAULT NULL, type VARCHAR(255) NOT NULL, priority INT NOT NULL, active TINYINT NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4', $table));
        }
        $this->addSql('CREATE TABLE map_area (id INT AUTO_INCREMENT NOT NULL, plan_id INT NOT NULL, title VARCHAR(255) NOT NULL, active TINYINT NOT NULL, INDEX IDX_MAP_AREA_PLAN (plan_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE map_area_point (id INT AUTO_INCREMENT NOT NULL, area_id INT NOT NULL, x DOUBLE PRECISION NOT NULL, y DOUBLE PRECISION NOT NULL, latitude DOUBLE PRECISION DEFAULT NULL, longitude DOUBLE PRECISION DEFAULT NULL, position INT NOT NULL, INDEX IDX_MAP_AREA_POINT_AREA (area_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE map_area ADD CONSTRAINT FK_MAP_AREA_PLAN FOREIGN KEY (plan_id) REFERENCES map_plan (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE map_area_point ADD CONSTRAINT FK_MAP_AREA_POINT_AREA FOREIGN KEY (area_id) REFERENCES map_area (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE map_place DROP FOREIGN KEY FK_F4EB1CA1460D9FD7');
        $this->addSql('ALTER TABLE map_place CHANGE node_id node_id INT DEFAULT NULL, ADD area_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE map_place ADD CONSTRAINT FK_MAP_PLACE_NODE_EDITOR FOREIGN KEY (node_id) REFERENCES map_node (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE map_place ADD CONSTRAINT FK_MAP_PLACE_AREA_EDITOR FOREIGN KEY (area_id) REFERENCES map_area (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_MAP_PLACE_AREA_EDITOR ON map_place (area_id)');

        $this->addSql('ALTER TABLE kiosk_terminal DROP FOREIGN KEY FK_C88B6136B6C8C304');
        $this->addSql('ALTER TABLE kiosk_terminal CHANGE start_node_id start_node_id INT DEFAULT NULL, ADD area_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE kiosk_terminal ADD CONSTRAINT FK_TERMINAL_NODE_EDITOR FOREIGN KEY (start_node_id) REFERENCES map_node (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE kiosk_terminal ADD CONSTRAINT FK_TERMINAL_AREA_EDITOR FOREIGN KEY (area_id) REFERENCES map_area (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_TERMINAL_AREA_EDITOR ON kiosk_terminal (area_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE kiosk_terminal DROP FOREIGN KEY FK_TERMINAL_NODE_EDITOR');
        $this->addSql('ALTER TABLE kiosk_terminal DROP FOREIGN KEY FK_TERMINAL_AREA_EDITOR');
        $this->addSql('DROP INDEX IDX_TERMINAL_AREA_EDITOR ON kiosk_terminal');
        $this->addSql('ALTER TABLE kiosk_terminal DROP area_id, CHANGE start_node_id start_node_id INT NOT NULL');
        $this->addSql('ALTER TABLE kiosk_terminal ADD CONSTRAINT FK_C88B6136B6C8C304 FOREIGN KEY (start_node_id) REFERENCES map_node (id)');
        $this->addSql('ALTER TABLE map_place DROP FOREIGN KEY FK_MAP_PLACE_NODE_EDITOR');
        $this->addSql('ALTER TABLE map_place DROP FOREIGN KEY FK_MAP_PLACE_AREA_EDITOR');
        $this->addSql('DROP INDEX IDX_MAP_PLACE_AREA_EDITOR ON map_place');
        $this->addSql('ALTER TABLE map_place DROP area_id, CHANGE node_id node_id INT NOT NULL');
        $this->addSql('ALTER TABLE map_place ADD CONSTRAINT FK_F4EB1CA1460D9FD7 FOREIGN KEY (node_id) REFERENCES map_node (id) ON DELETE CASCADE');
        $this->addSql('DROP TABLE map_area_point');
        $this->addSql('DROP TABLE map_area');
        foreach (['standby_media', 'news_poster', 'animation_poster', 'gallery_media', 'taganrog_media', 'map_icon'] as $table) $this->addSql('DROP TABLE '.$table);
    }
}
