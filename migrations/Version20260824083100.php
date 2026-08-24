<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260824083100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Структурированные страницы правил проживания и Каникул в Таганроге';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE content_page ADD fifth_image_file_name VARCHAR(255) DEFAULT NULL, ADD fifth_image_updated_at DATETIME DEFAULT NULL');
        $this->addSql("UPDATE content_page SET document_file_name = NULL, document_updated_at = NULL WHERE type IN ('residence_rules', 'taganrog')");
        $this->addSql('DROP TABLE taganrog_media');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE taganrog_media (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, file_name VARCHAR(255) DEFAULT NULL, type VARCHAR(255) NOT NULL, priority INT NOT NULL, active TINYINT NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE content_page DROP fifth_image_file_name, DROP fifth_image_updated_at');
    }
}
