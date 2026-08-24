<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260824082500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Возвращает постеры анимационной программы';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE animation_poster (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, file_name VARCHAR(255) DEFAULT NULL, type VARCHAR(255) NOT NULL, priority INT NOT NULL, active TINYINT NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE animation_poster');
    }
}
