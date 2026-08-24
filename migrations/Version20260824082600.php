<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260824082600 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Добавляет второе изображение маскота для страницы медицинского центра';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE content_page ADD mascot_two_file_name VARCHAR(255) DEFAULT NULL, ADD mascot_two_updated_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE content_page DROP mascot_two_file_name, DROP mascot_two_updated_at');
    }
}
