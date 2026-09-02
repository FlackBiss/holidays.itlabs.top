<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260825175500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds an ordered image slider collection to the Taganrog page';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE taganrog_slider_image (id INT AUTO_INCREMENT NOT NULL, page_id INT NOT NULL, file_name VARCHAR(255) DEFAULT NULL, priority INT NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX taganrog_slider_image_page_idx (page_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE taganrog_slider_image ADD CONSTRAINT FK_TAGANROG_SLIDER_IMAGE_PAGE FOREIGN KEY (page_id) REFERENCES content_page (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE taganrog_slider_image DROP FOREIGN KEY FK_TAGANROG_SLIDER_IMAGE_PAGE');
        $this->addSql('DROP TABLE taganrog_slider_image');
    }
}
