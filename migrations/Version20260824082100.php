<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260824082100 extends AbstractMigration
{
    public function getDescription(): string { return 'Удаление MediaAsset: прямые Vich-файлы страниц, настроек, карты и структурированных записей'; }

    public function up(Schema $schema): void
    {
        $this->addSql("DELETE FROM content_item WHERE section IN ('standby', 'guest_document', 'animation', 'news', 'gallery', 'price_category', 'price', 'uav_memo', 'taganrog_media', 'promo')");
        $this->addSql("DELETE FROM content_item WHERE section = 'service_schedule' AND JSON_UNQUOTE(JSON_EXTRACT(data, '$.kind')) = 'full_schedule'");
        $this->addSql('ALTER TABLE content_item ADD file_name VARCHAR(255) DEFAULT NULL, ADD file_type VARCHAR(255) DEFAULT NULL, ADD file_updated_at DATETIME DEFAULT NULL');
        $this->addSql('UPDATE content_item ci LEFT JOIN media_asset ma ON ma.id = ci.media_id SET ci.file_name = ma.file_name, ci.file_type = ma.type WHERE ci.media_id IS NOT NULL');
        $this->addSql('ALTER TABLE content_item DROP FOREIGN KEY FK_D279C8DBEA9FDD75');
        $this->addSql('DROP INDEX IDX_D279C8DBEA9FDD75 ON content_item');
        $this->addSql('ALTER TABLE content_item DROP media_id');
        $this->addSql('DROP TABLE content_item_media');

        $this->addSql('ALTER TABLE content_page ADD image_file_name VARCHAR(255) DEFAULT NULL, ADD image_updated_at DATETIME DEFAULT NULL, ADD logo_file_name VARCHAR(255) DEFAULT NULL, ADD logo_updated_at DATETIME DEFAULT NULL');
        $this->addSql('UPDATE content_page cp LEFT JOIN media_asset image ON image.id = cp.image_id LEFT JOIN media_asset logo ON logo.id = cp.logo_id SET cp.image_file_name = image.file_name, cp.logo_file_name = logo.file_name');
        $this->addSql('ALTER TABLE content_page DROP FOREIGN KEY FK_D9685BE53DA5256D');
        $this->addSql('ALTER TABLE content_page DROP FOREIGN KEY FK_D9685BE5F98F144A');
        $this->addSql('DROP INDEX IDX_D9685BE53DA5256D ON content_page');
        $this->addSql('DROP INDEX IDX_D9685BE5F98F144A ON content_page');
        $this->addSql('ALTER TABLE content_page DROP image_id, DROP logo_id');
        $this->addSql('DROP TABLE content_page_media');

        $this->addSql('ALTER TABLE map_plan ADD file_name VARCHAR(255) DEFAULT NULL, ADD file_type VARCHAR(255) DEFAULT NULL, ADD file_updated_at DATETIME DEFAULT NULL');
        $this->addSql('UPDATE map_plan mp LEFT JOIN media_asset ma ON ma.id = mp.image_id SET mp.file_name = ma.file_name, mp.file_type = ma.type');
        $this->addSql('ALTER TABLE map_plan DROP FOREIGN KEY FK_4E72FCE83DA5256D');
        $this->addSql('DROP INDEX IDX_4E72FCE83DA5256D ON map_plan');
        $this->addSql('ALTER TABLE map_plan DROP image_id');

        $this->addSql('ALTER TABLE site_settings ADD file_name VARCHAR(255) DEFAULT NULL, ADD file_type VARCHAR(255) DEFAULT NULL, ADD file_updated_at DATETIME DEFAULT NULL');
        $this->addSql('UPDATE site_settings ss LEFT JOIN media_asset ma ON ma.id = ss.logo_id SET ss.file_name = ma.file_name, ss.file_type = ma.type');
        $this->addSql('ALTER TABLE site_settings DROP FOREIGN KEY FK_E9081F1FF98F144A');
        $this->addSql('DROP INDEX IDX_E9081F1FF98F144A ON site_settings');
        $this->addSql('ALTER TABLE site_settings DROP logo_id');

        $this->addSql('ALTER TABLE map_place DROP FOREIGN KEY FK_F4EB1CA154B9D732');
        $this->addSql('ALTER TABLE map_place DROP FOREIGN KEY FK_F4EB1CA1922726E9');
        $this->addSql('DROP INDEX IDX_F4EB1CA154B9D732 ON map_place');
        $this->addSql('DROP INDEX IDX_F4EB1CA1922726E9 ON map_place');
        $this->addSql('ALTER TABLE map_place DROP cover_id, CHANGE icon_id icon_id INT DEFAULT NULL');
        $this->addSql('UPDATE map_place SET icon_id = NULL');
        $this->addSql('ALTER TABLE map_place ADD CONSTRAINT FK_F4EB1CA154B9D732 FOREIGN KEY (icon_id) REFERENCES map_icon (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_F4EB1CA154B9D732 ON map_place (icon_id)');

        $this->addSql('DROP TABLE media_asset');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Старая универсальная медиатека намеренно удалена и не восстанавливается автоматически.');
    }
}
