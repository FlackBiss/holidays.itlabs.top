<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260824082300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Выносит категории номеров в общий справочник и связывает их с корпусами many-to-many';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE map_place_room_category (map_place_id INT NOT NULL, room_category_id INT NOT NULL, INDEX IDX_MP_ROOM_PLACE (map_place_id), INDEX IDX_MP_ROOM_CATEGORY (room_category_id), PRIMARY KEY (map_place_id, room_category_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('INSERT INTO map_place_room_category (map_place_id, room_category_id) SELECT place_id, id FROM room_category');
        $this->addSql('INSERT IGNORE INTO map_place_room_category (map_place_id, room_category_id) SELECT j.map_place_id, MIN(same_title.id) FROM map_place_room_category j INNER JOIN room_category current_category ON current_category.id = j.room_category_id INNER JOIN room_category same_title ON same_title.title = current_category.title GROUP BY j.map_place_id, current_category.title');
        $this->addSql('DELETE j FROM map_place_room_category j INNER JOIN room_category duplicate_category ON duplicate_category.id = j.room_category_id INNER JOIN room_category earlier_category ON earlier_category.title = duplicate_category.title AND earlier_category.id < duplicate_category.id');
        $this->addSql('DELETE duplicate_category FROM room_category duplicate_category INNER JOIN room_category earlier_category ON earlier_category.title = duplicate_category.title AND earlier_category.id < duplicate_category.id');
        $this->addSql('ALTER TABLE room_category DROP FOREIGN KEY FK_A6AAD905DA6A219');
        $this->addSql('DROP INDEX IDX_A6AAD905DA6A219 ON room_category');
        $this->addSql('ALTER TABLE room_category DROP place_id');
        $this->addSql('ALTER TABLE map_place_room_category ADD CONSTRAINT FK_MP_ROOM_PLACE FOREIGN KEY (map_place_id) REFERENCES map_place (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE map_place_room_category ADD CONSTRAINT FK_MP_ROOM_CATEGORY FOREIGN KEY (room_category_id) REFERENCES room_category (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE room_category ADD place_id INT DEFAULT NULL');
        $this->addSql('UPDATE room_category r SET place_id = (SELECT MIN(j.map_place_id) FROM map_place_room_category j WHERE j.room_category_id = r.id)');
        $this->addSql('DELETE FROM room_category WHERE place_id IS NULL');
        $this->addSql('ALTER TABLE room_category MODIFY place_id INT NOT NULL');
        $this->addSql('CREATE INDEX IDX_A6AAD905DA6A219 ON room_category (place_id)');
        $this->addSql('ALTER TABLE room_category ADD CONSTRAINT FK_A6AAD905DA6A219 FOREIGN KEY (place_id) REFERENCES map_place (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE map_place_room_category DROP FOREIGN KEY FK_MP_ROOM_PLACE');
        $this->addSql('ALTER TABLE map_place_room_category DROP FOREIGN KEY FK_MP_ROOM_CATEGORY');
        $this->addSql('DROP TABLE map_place_room_category');
    }
}
