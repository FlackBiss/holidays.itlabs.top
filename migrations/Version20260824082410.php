<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260824082410 extends AbstractMigration
{
    public function getDescription(): string { return 'Синхронизирует индекс коллекции QR-кодов с Doctrine'; }
    public function up(Schema $schema): void { $this->addSql('ALTER TABLE service_qr_link RENAME INDEX idx_service_qr_page TO IDX_F3B6E440C4663E4'); }
    public function down(Schema $schema): void { $this->addSql('ALTER TABLE service_qr_link RENAME INDEX IDX_F3B6E440C4663E4 TO idx_service_qr_page'); }
}
