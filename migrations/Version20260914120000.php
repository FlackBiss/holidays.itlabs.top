<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260914120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Stores a transactional content revision for frontend change polling';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE content_revision (id INT NOT NULL, version VARCHAR(32) NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('INSERT INTO content_revision (id, version, updated_at) VALUES (1, ?, ?)', [bin2hex(random_bytes(16)), gmdate('Y-m-d H:i:s')]);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE content_revision');
    }
}
