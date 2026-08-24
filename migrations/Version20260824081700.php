<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260824081700 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Прямая Vich-загрузка файлов экранов и удаление старой связи с MediaAsset';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE section_document SET parent_id = NULL WHERE section = 'residence-rules' AND parent_id IS NOT NULL");
        $this->addSql("DELETE FROM section_document WHERE section = 'residence-rules' AND title = 'Документы по правилам проживания'");
        $this->addSql("DELETE FROM section_document WHERE section = 'prices' AND parent_id IS NOT NULL");
        $this->addSql('ALTER TABLE section_document DROP FOREIGN KEY FK_A68E39C9EA9FDD75');
        $this->addSql('DROP INDEX IDX_4804D02AEA9FDD75 ON section_document');
        $this->addSql('ALTER TABLE section_document ADD file_name VARCHAR(255) DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL, DROP media_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE section_document ADD media_id INT DEFAULT NULL, DROP file_name, DROP updated_at');
        $this->addSql('ALTER TABLE section_document ADD CONSTRAINT FK_A68E39C9EA9FDD75 FOREIGN KEY (media_id) REFERENCES media_asset (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_4804D02AEA9FDD75 ON section_document (media_id)');
    }
}
