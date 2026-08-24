<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260824081600 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Синхронизация имён индексов файлов разделов с Doctrine mapping';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE section_document RENAME INDEX IDX_A68E39C9727ACA70 TO IDX_4804D02A727ACA70');
        $this->addSql('ALTER TABLE section_document RENAME INDEX IDX_A68E39C9EA9FDD75 TO IDX_4804D02AEA9FDD75');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE section_document RENAME INDEX IDX_4804D02A727ACA70 TO IDX_A68E39C9727ACA70');
        $this->addSql('ALTER TABLE section_document RENAME INDEX IDX_4804D02AEA9FDD75 TO IDX_A68E39C9EA9FDD75');
    }
}
