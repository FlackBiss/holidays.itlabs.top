<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260824082800 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Заменяет старое время питания на страницу с описаниями и четырьмя изображениями';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE content_page ADD extra_image_file_name VARCHAR(255) DEFAULT NULL, ADD extra_image_updated_at DATETIME DEFAULT NULL');
        $this->addSql("DELETE FROM content_item WHERE section = 'meal_schedule'");
        $this->addSql("DELETE FROM section_document WHERE section = 'meal-times'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE content_page DROP extra_image_file_name, DROP extra_image_updated_at');
    }
}
