<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260824082310 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Синхронизирует имена индексов связи корпусов и категорий номеров с Doctrine';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE map_place_room_category RENAME INDEX idx_mp_room_place TO IDX_F442434E6F54943E');
        $this->addSql('ALTER TABLE map_place_room_category RENAME INDEX idx_mp_room_category TO IDX_F442434E67333DD');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE map_place_room_category RENAME INDEX IDX_F442434E6F54943E TO idx_mp_room_place');
        $this->addSql('ALTER TABLE map_place_room_category RENAME INDEX IDX_F442434E67333DD TO idx_mp_room_category');
    }
}
