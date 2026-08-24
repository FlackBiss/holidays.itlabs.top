<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260824082200 extends AbstractMigration
{
    public function getDescription(): string { return 'Синхронизация имён индексов редактора карты с Doctrine'; }
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE map_area_point RENAME INDEX idx_map_area_point_area TO IDX_2F10FEF0BD0F409C');
        $this->addSql('ALTER TABLE kiosk_terminal RENAME INDEX idx_terminal_area_editor TO IDX_C88B6136BD0F409C');
        $this->addSql('ALTER TABLE map_place RENAME INDEX idx_map_place_area_editor TO IDX_F4EB1CA1BD0F409C');
        $this->addSql('ALTER TABLE map_area RENAME INDEX idx_map_area_plan TO IDX_44BC9AFDE899029B');
    }
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE map_area_point RENAME INDEX IDX_2F10FEF0BD0F409C TO idx_map_area_point_area');
        $this->addSql('ALTER TABLE kiosk_terminal RENAME INDEX IDX_C88B6136BD0F409C TO idx_terminal_area_editor');
        $this->addSql('ALTER TABLE map_place RENAME INDEX IDX_F4EB1CA1BD0F409C TO idx_map_place_area_editor');
        $this->addSql('ALTER TABLE map_area RENAME INDEX IDX_44BC9AFDE899029B TO idx_map_area_plan');
    }
}
