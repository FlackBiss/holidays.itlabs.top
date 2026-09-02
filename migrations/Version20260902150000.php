<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260902150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Aligns geocalibration index and immutable datetime columns with Doctrine metadata';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE map_geo_control_point RENAME INDEX IDX_GEO_CONTROL_CALIBRATION TO IDX_AE6E5FB68DE210C5');
        $this->addSql('ALTER TABLE map_geo_calibration CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE map_geo_control_point RENAME INDEX IDX_AE6E5FB68DE210C5 TO IDX_GEO_CONTROL_CALIBRATION');
        $this->addSql("ALTER TABLE map_geo_calibration CHANGE created_at created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', CHANGE updated_at updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)'");
    }
}
