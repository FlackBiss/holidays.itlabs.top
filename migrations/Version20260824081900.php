<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260824081900 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Самостоятельные категории номеров с упорядоченными Vich-фотографиями';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE room_category_photo (id INT AUTO_INCREMENT NOT NULL, category_id INT NOT NULL, file_name VARCHAR(255) DEFAULT NULL, priority INT NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX room_category_photo_category_idx (category_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE room_category_photo ADD CONSTRAINT FK_ROOM_CATEGORY_PHOTO_CATEGORY FOREIGN KEY (category_id) REFERENCES room_category (id) ON DELETE CASCADE');
        $this->addSql('DROP TABLE room_category_media');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE room_category_media (room_category_id INT NOT NULL, media_asset_id INT NOT NULL, INDEX IDX_ROOM_CATEGORY_MEDIA_CATEGORY (room_category_id), INDEX IDX_ROOM_CATEGORY_MEDIA_ASSET (media_asset_id), PRIMARY KEY (room_category_id, media_asset_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE room_category_media ADD CONSTRAINT FK_ROOM_CATEGORY_MEDIA_CATEGORY FOREIGN KEY (room_category_id) REFERENCES room_category (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE room_category_media ADD CONSTRAINT FK_ROOM_CATEGORY_MEDIA_ASSET FOREIGN KEY (media_asset_id) REFERENCES media_asset (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE room_category_photo DROP FOREIGN KEY FK_ROOM_CATEGORY_PHOTO_CATEGORY');
        $this->addSql('DROP TABLE room_category_photo');
    }
}
