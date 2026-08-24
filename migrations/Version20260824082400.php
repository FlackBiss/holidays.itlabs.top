<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260824082400 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'PDF-разделы, единая страница времени работы и коллекция QR-кодов';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE content_page ADD document_file_name VARCHAR(255) DEFAULT NULL, ADD document_updated_at DATETIME DEFAULT NULL');
        $this->addSql('CREATE TABLE service_qr_link (id INT AUTO_INCREMENT NOT NULL, page_id INT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, file_name VARCHAR(255) DEFAULT NULL, priority INT NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_SERVICE_QR_PAGE (page_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE service_qr_link ADD CONSTRAINT FK_SERVICE_QR_PAGE FOREIGN KEY (page_id) REFERENCES content_page (id) ON DELETE CASCADE');
        $this->addSql("INSERT INTO content_page (type, title, description, data, active) SELECT 'service_hours', 'Время работы служб', NULL, '{}', 1 WHERE NOT EXISTS (SELECT 1 FROM content_page WHERE type = 'service_hours')");
        $this->addSql("INSERT INTO service_qr_link (page_id, title, description, priority) SELECT page.id, item.title, CONCAT_WS('\n', item.description, item.url), item.priority FROM content_item item INNER JOIN content_page page ON page.type = 'service_hours' WHERE item.section = 'qr_link'");
        $this->addSql("DELETE FROM content_item WHERE section IN ('qr_link', 'service_schedule', 'connect_benefit', 'connect_warning', 'connect_reward', 'transfer', 'public_transport', 'taganrog_about')");
        $this->addSql("DELETE FROM section_document WHERE section IN ('service-hours', 'residence-rules')");
        $this->addSql('DROP TABLE animation_poster');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Удалённые устаревшие структуры контента автоматически не восстанавливаются.');
    }
}
