<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260824081800 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Упорядоченная Vich-фотогалерея объектов карты';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE map_place_photo (id INT AUTO_INCREMENT NOT NULL, place_id INT NOT NULL, file_name VARCHAR(255) DEFAULT NULL, priority INT NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX map_place_photo_place_idx (place_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE map_place_photo ADD CONSTRAINT FK_MAP_PLACE_PHOTO_PLACE FOREIGN KEY (place_id) REFERENCES map_place (id) ON DELETE CASCADE');
        $this->addSql('DROP TABLE map_place_media');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE map_place_media (map_place_id INT NOT NULL, media_asset_id INT NOT NULL, INDEX IDX_24F7E86D6F54943E (map_place_id), INDEX IDX_24F7E86DABB37F3 (media_asset_id), PRIMARY KEY (map_place_id, media_asset_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE map_place_media ADD CONSTRAINT FK_24F7E86D6F54943E FOREIGN KEY (map_place_id) REFERENCES map_place (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE map_place_media ADD CONSTRAINT FK_24F7E86DABB37F3 FOREIGN KEY (media_asset_id) REFERENCES media_asset (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE map_place_photo DROP FOREIGN KEY FK_MAP_PLACE_PHOTO_PLACE');
        $this->addSql('DROP TABLE map_place_photo');
    }
}
