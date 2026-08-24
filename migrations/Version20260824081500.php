<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260824081500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Универсальные PDF и изображения разделов с вложенными категориями';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE section_document (id INT AUTO_INCREMENT NOT NULL, section VARCHAR(255) NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, external_url VARCHAR(2048) DEFAULT NULL, priority INT NOT NULL, active TINYINT NOT NULL, parent_id INT DEFAULT NULL, media_id INT DEFAULT NULL, INDEX IDX_A68E39C9727ACA70 (parent_id), INDEX IDX_A68E39C9EA9FDD75 (media_id), INDEX section_document_section_idx (section, active, priority), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE section_document ADD CONSTRAINT FK_A68E39C9727ACA70 FOREIGN KEY (parent_id) REFERENCES section_document (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE section_document ADD CONSTRAINT FK_A68E39C9EA9FDD75 FOREIGN KEY (media_id) REFERENCES media_asset (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE section_document DROP FOREIGN KEY FK_A68E39C9727ACA70');
        $this->addSql('ALTER TABLE section_document DROP FOREIGN KEY FK_A68E39C9EA9FDD75');
        $this->addSql('DROP TABLE section_document');
    }
}
