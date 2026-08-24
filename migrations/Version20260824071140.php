<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260824071140 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE content_item (id INT AUTO_INCREMENT NOT NULL, section VARCHAR(255) NOT NULL, title VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, territory VARCHAR(255) DEFAULT NULL, phone VARCHAR(255) DEFAULT NULL, working_days VARCHAR(255) DEFAULT NULL, starts_at VARCHAR(5) DEFAULT NULL, ends_at VARCHAR(5) DEFAULT NULL, break_starts_at VARCHAR(5) DEFAULT NULL, break_ends_at VARCHAR(5) DEFAULT NULL, round_the_clock TINYINT NOT NULL, times JSON NOT NULL, weekdays_times JSON NOT NULL, weekends_times JSON NOT NULL, url VARCHAR(2048) DEFAULT NULL, online_booking TINYINT NOT NULL, points INT DEFAULT NULL, priority INT NOT NULL, data JSON NOT NULL, active TINYINT NOT NULL, parent_id INT DEFAULT NULL, media_id INT DEFAULT NULL, INDEX IDX_D279C8DB727ACA70 (parent_id), INDEX IDX_D279C8DBEA9FDD75 (media_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE content_item_media (content_item_id INT NOT NULL, media_asset_id INT NOT NULL, INDEX IDX_D9B20C2ECD678BED (content_item_id), INDEX IDX_D9B20C2EABB37F3 (media_asset_id), PRIMARY KEY (content_item_id, media_asset_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE content_page (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(255) NOT NULL, title VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, data JSON NOT NULL, active TINYINT NOT NULL, image_id INT DEFAULT NULL, logo_id INT DEFAULT NULL, INDEX IDX_D9685BE53DA5256D (image_id), INDEX IDX_D9685BE5F98F144A (logo_id), UNIQUE INDEX uniq_content_page_type (type), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE content_page_media (content_page_id INT NOT NULL, media_asset_id INT NOT NULL, INDEX IDX_9594E828D34EBA57 (content_page_id), INDEX IDX_9594E828ABB37F3 (media_asset_id), PRIMARY KEY (content_page_id, media_asset_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE kiosk_terminal (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(64) NOT NULL, name VARCHAR(255) NOT NULL, active TINYINT NOT NULL, last_seen_at DATETIME DEFAULT NULL, start_node_id INT NOT NULL, INDEX IDX_C88B6136B6C8C304 (start_node_id), UNIQUE INDEX uniq_terminal_code (code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE map_edge (id INT AUTO_INCREMENT NOT NULL, bidirectional TINYINT NOT NULL, `accessible` TINYINT NOT NULL, distance_meters DOUBLE PRECISION DEFAULT NULL, active TINYINT NOT NULL, plan_id INT NOT NULL, from_node_id INT NOT NULL, to_node_id INT NOT NULL, INDEX IDX_E62E74F3E899029B (plan_id), INDEX IDX_E62E74F3C0537C78 (from_node_id), INDEX IDX_E62E74F3C895A222 (to_node_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE map_node (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) DEFAULT NULL, x DOUBLE PRECISION NOT NULL, y DOUBLE PRECISION NOT NULL, latitude DOUBLE PRECISION DEFAULT NULL, longitude DOUBLE PRECISION DEFAULT NULL, active TINYINT NOT NULL, plan_id INT NOT NULL, INDEX IDX_16574FD0E899029B (plan_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE map_place (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, building_number VARCHAR(64) DEFAULT NULL, floor_count INT DEFAULT NULL, room_count INT DEFAULT NULL, working_hours VARCHAR(255) DEFAULT NULL, phone VARCHAR(255) DEFAULT NULL, online_booking TINYINT NOT NULL, booking_url VARCHAR(2048) DEFAULT NULL, search_aliases JSON NOT NULL, priority INT NOT NULL, active TINYINT NOT NULL, plan_id INT NOT NULL, node_id INT NOT NULL, icon_id INT DEFAULT NULL, cover_id INT DEFAULT NULL, INDEX IDX_F4EB1CA1E899029B (plan_id), INDEX IDX_F4EB1CA1460D9FD7 (node_id), INDEX IDX_F4EB1CA154B9D732 (icon_id), INDEX IDX_F4EB1CA1922726E9 (cover_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE map_place_media (map_place_id INT NOT NULL, media_asset_id INT NOT NULL, INDEX IDX_24F7E86D6F54943E (map_place_id), INDEX IDX_24F7E86DABB37F3 (media_asset_id), PRIMARY KEY (map_place_id, media_asset_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE map_plan (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, territory VARCHAR(64) NOT NULL, width INT NOT NULL, height INT NOT NULL, active TINYINT NOT NULL, image_id INT NOT NULL, INDEX IDX_4E72FCE83DA5256D (image_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE media_asset (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, file_name VARCHAR(255) DEFAULT NULL, sort_order INT NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE room_category (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, priority INT NOT NULL, place_id INT NOT NULL, INDEX IDX_A6AAD905DA6A219 (place_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE room_category_media (room_category_id INT NOT NULL, media_asset_id INT NOT NULL, INDEX IDX_CB1F19B867333DD (room_category_id), INDEX IDX_CB1F19B8ABB37F3 (media_asset_id), PRIMARY KEY (room_category_id, media_asset_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE site_settings (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(32) NOT NULL, company_name VARCHAR(255) NOT NULL, latitude DOUBLE PRECISION NOT NULL, longitude DOUBLE PRECISION NOT NULL, weather_cache_ttl INT NOT NULL, idle_timeout_seconds INT NOT NULL, slide_duration_seconds INT NOT NULL, mobile_map_url VARCHAR(2048) DEFAULT NULL, max_geo_snap_distance_meters INT NOT NULL, logo_id INT DEFAULT NULL, INDEX IDX_E9081F1FF98F144A (logo_id), UNIQUE INDEX uniq_settings_code (code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_8D93D649F85E0677 (username), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE content_item ADD CONSTRAINT FK_D279C8DB727ACA70 FOREIGN KEY (parent_id) REFERENCES content_item (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE content_item ADD CONSTRAINT FK_D279C8DBEA9FDD75 FOREIGN KEY (media_id) REFERENCES media_asset (id)');
        $this->addSql('ALTER TABLE content_item_media ADD CONSTRAINT FK_D9B20C2ECD678BED FOREIGN KEY (content_item_id) REFERENCES content_item (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE content_item_media ADD CONSTRAINT FK_D9B20C2EABB37F3 FOREIGN KEY (media_asset_id) REFERENCES media_asset (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE content_page ADD CONSTRAINT FK_D9685BE53DA5256D FOREIGN KEY (image_id) REFERENCES media_asset (id)');
        $this->addSql('ALTER TABLE content_page ADD CONSTRAINT FK_D9685BE5F98F144A FOREIGN KEY (logo_id) REFERENCES media_asset (id)');
        $this->addSql('ALTER TABLE content_page_media ADD CONSTRAINT FK_9594E828D34EBA57 FOREIGN KEY (content_page_id) REFERENCES content_page (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE content_page_media ADD CONSTRAINT FK_9594E828ABB37F3 FOREIGN KEY (media_asset_id) REFERENCES media_asset (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE kiosk_terminal ADD CONSTRAINT FK_C88B6136B6C8C304 FOREIGN KEY (start_node_id) REFERENCES map_node (id)');
        $this->addSql('ALTER TABLE map_edge ADD CONSTRAINT FK_E62E74F3E899029B FOREIGN KEY (plan_id) REFERENCES map_plan (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE map_edge ADD CONSTRAINT FK_E62E74F3C0537C78 FOREIGN KEY (from_node_id) REFERENCES map_node (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE map_edge ADD CONSTRAINT FK_E62E74F3C895A222 FOREIGN KEY (to_node_id) REFERENCES map_node (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE map_node ADD CONSTRAINT FK_16574FD0E899029B FOREIGN KEY (plan_id) REFERENCES map_plan (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE map_place ADD CONSTRAINT FK_F4EB1CA1E899029B FOREIGN KEY (plan_id) REFERENCES map_plan (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE map_place ADD CONSTRAINT FK_F4EB1CA1460D9FD7 FOREIGN KEY (node_id) REFERENCES map_node (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE map_place ADD CONSTRAINT FK_F4EB1CA154B9D732 FOREIGN KEY (icon_id) REFERENCES media_asset (id)');
        $this->addSql('ALTER TABLE map_place ADD CONSTRAINT FK_F4EB1CA1922726E9 FOREIGN KEY (cover_id) REFERENCES media_asset (id)');
        $this->addSql('ALTER TABLE map_place_media ADD CONSTRAINT FK_24F7E86D6F54943E FOREIGN KEY (map_place_id) REFERENCES map_place (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE map_place_media ADD CONSTRAINT FK_24F7E86DABB37F3 FOREIGN KEY (media_asset_id) REFERENCES media_asset (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE map_plan ADD CONSTRAINT FK_4E72FCE83DA5256D FOREIGN KEY (image_id) REFERENCES media_asset (id)');
        $this->addSql('ALTER TABLE room_category ADD CONSTRAINT FK_A6AAD905DA6A219 FOREIGN KEY (place_id) REFERENCES map_place (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE room_category_media ADD CONSTRAINT FK_CB1F19B867333DD FOREIGN KEY (room_category_id) REFERENCES room_category (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE room_category_media ADD CONSTRAINT FK_CB1F19B8ABB37F3 FOREIGN KEY (media_asset_id) REFERENCES media_asset (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE site_settings ADD CONSTRAINT FK_E9081F1FF98F144A FOREIGN KEY (logo_id) REFERENCES media_asset (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE content_item DROP FOREIGN KEY FK_D279C8DB727ACA70');
        $this->addSql('ALTER TABLE content_item DROP FOREIGN KEY FK_D279C8DBEA9FDD75');
        $this->addSql('ALTER TABLE content_item_media DROP FOREIGN KEY FK_D9B20C2ECD678BED');
        $this->addSql('ALTER TABLE content_item_media DROP FOREIGN KEY FK_D9B20C2EABB37F3');
        $this->addSql('ALTER TABLE content_page DROP FOREIGN KEY FK_D9685BE53DA5256D');
        $this->addSql('ALTER TABLE content_page DROP FOREIGN KEY FK_D9685BE5F98F144A');
        $this->addSql('ALTER TABLE content_page_media DROP FOREIGN KEY FK_9594E828D34EBA57');
        $this->addSql('ALTER TABLE content_page_media DROP FOREIGN KEY FK_9594E828ABB37F3');
        $this->addSql('ALTER TABLE kiosk_terminal DROP FOREIGN KEY FK_C88B6136B6C8C304');
        $this->addSql('ALTER TABLE map_edge DROP FOREIGN KEY FK_E62E74F3E899029B');
        $this->addSql('ALTER TABLE map_edge DROP FOREIGN KEY FK_E62E74F3C0537C78');
        $this->addSql('ALTER TABLE map_edge DROP FOREIGN KEY FK_E62E74F3C895A222');
        $this->addSql('ALTER TABLE map_node DROP FOREIGN KEY FK_16574FD0E899029B');
        $this->addSql('ALTER TABLE map_place DROP FOREIGN KEY FK_F4EB1CA1E899029B');
        $this->addSql('ALTER TABLE map_place DROP FOREIGN KEY FK_F4EB1CA1460D9FD7');
        $this->addSql('ALTER TABLE map_place DROP FOREIGN KEY FK_F4EB1CA154B9D732');
        $this->addSql('ALTER TABLE map_place DROP FOREIGN KEY FK_F4EB1CA1922726E9');
        $this->addSql('ALTER TABLE map_place_media DROP FOREIGN KEY FK_24F7E86D6F54943E');
        $this->addSql('ALTER TABLE map_place_media DROP FOREIGN KEY FK_24F7E86DABB37F3');
        $this->addSql('ALTER TABLE map_plan DROP FOREIGN KEY FK_4E72FCE83DA5256D');
        $this->addSql('ALTER TABLE room_category DROP FOREIGN KEY FK_A6AAD905DA6A219');
        $this->addSql('ALTER TABLE room_category_media DROP FOREIGN KEY FK_CB1F19B867333DD');
        $this->addSql('ALTER TABLE room_category_media DROP FOREIGN KEY FK_CB1F19B8ABB37F3');
        $this->addSql('ALTER TABLE site_settings DROP FOREIGN KEY FK_E9081F1FF98F144A');
        $this->addSql('DROP TABLE content_item');
        $this->addSql('DROP TABLE content_item_media');
        $this->addSql('DROP TABLE content_page');
        $this->addSql('DROP TABLE content_page_media');
        $this->addSql('DROP TABLE kiosk_terminal');
        $this->addSql('DROP TABLE map_edge');
        $this->addSql('DROP TABLE map_node');
        $this->addSql('DROP TABLE map_place');
        $this->addSql('DROP TABLE map_place_media');
        $this->addSql('DROP TABLE map_plan');
        $this->addSql('DROP TABLE media_asset');
        $this->addSql('DROP TABLE room_category');
        $this->addSql('DROP TABLE room_category_media');
        $this->addSql('DROP TABLE site_settings');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
