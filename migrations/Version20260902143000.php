<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260902143000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds versioned piecewise-affine map geocalibration and node coordinate provenance';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE map_geo_calibration (id INT AUTO_INCREMENT NOT NULL, plan_id INT NOT NULL, method VARCHAR(32) NOT NULL, version INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX uniq_map_geo_calibration_plan (plan_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE map_geo_control_point (id INT AUTO_INCREMENT NOT NULL, calibration_id INT NOT NULL, x DOUBLE PRECISION NOT NULL, y DOUBLE PRECISION NOT NULL, latitude DOUBLE PRECISION NOT NULL, longitude DOUBLE PRECISION NOT NULL, position INT NOT NULL, INDEX IDX_GEO_CONTROL_CALIBRATION (calibration_id), UNIQUE INDEX uniq_geo_control_position (calibration_id, position), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE map_geo_calibration ADD CONSTRAINT FK_GEO_CALIBRATION_PLAN FOREIGN KEY (plan_id) REFERENCES map_plan (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE map_geo_control_point ADD CONSTRAINT FK_GEO_CONTROL_CALIBRATION FOREIGN KEY (calibration_id) REFERENCES map_geo_calibration (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE map_node ADD geo_source VARCHAR(16) DEFAULT NULL, ADD geo_calibration_version INT DEFAULT NULL');
        $this->addSql("UPDATE map_node SET geo_source = 'manual' WHERE latitude IS NOT NULL AND longitude IS NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE map_geo_control_point DROP FOREIGN KEY FK_GEO_CONTROL_CALIBRATION');
        $this->addSql('ALTER TABLE map_geo_calibration DROP FOREIGN KEY FK_GEO_CALIBRATION_PLAN');
        $this->addSql('DROP TABLE map_geo_control_point');
        $this->addSql('DROP TABLE map_geo_calibration');
        $this->addSql('ALTER TABLE map_node DROP geo_source, DROP geo_calibration_version');
    }
}
