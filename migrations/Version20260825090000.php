<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260825090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Переносит иконки из отдельного раздела непосредственно в объекты карты';
    }

    public function up(Schema $schema): void
    {
        $icons = $this->connection->fetchAllAssociative(<<<'SQL'
            SELECT place.id AS place_id, icon.file_name
            FROM map_place place
            LEFT JOIN map_icon icon ON icon.id = place.icon_id
            WHERE icon.file_name IS NOT NULL
            SQL);

        $sourceDirectory = dirname(__DIR__).'/public/uploads/section-media';
        $targetDirectory = dirname(__DIR__).'/public/uploads/map-place-icons';

        if ($icons !== [] && !is_dir($targetDirectory) && !mkdir($targetDirectory, 0775, true) && !is_dir($targetDirectory)) {
            throw new \RuntimeException('Не удалось создать каталог иконок объектов карты.');
        }

        $this->addSql('ALTER TABLE map_place ADD icon_file_name VARCHAR(255) DEFAULT NULL, ADD icon_updated_at DATETIME DEFAULT NULL');

        foreach ($icons as $icon) {
            $placeId = (int) $icon['place_id'];
            $sourceFileName = basename((string) $icon['file_name']);
            $targetFileName = sprintf('legacy-map-place-%d-%s', $placeId, $sourceFileName);
            $sourcePath = $sourceDirectory.'/'.$sourceFileName;
            $targetPath = $targetDirectory.'/'.$targetFileName;

            // A legacy database row may already point to a manually removed file.
            // In that case the old API was broken as well, so leave the new field
            // empty and allow an administrator to upload the icon again.
            if (!is_file($sourcePath)) continue;
            if (!is_file($targetPath) && !copy($sourcePath, $targetPath)) {
                throw new \RuntimeException(sprintf('Не удалось перенести иконку объекта %d.', $placeId));
            }

            $this->addSql(
                'UPDATE map_place SET icon_file_name = :fileName WHERE id = :placeId',
                ['fileName' => $targetFileName, 'placeId' => $placeId],
            );
        }

        $this->addSql('ALTER TABLE map_place DROP FOREIGN KEY FK_F4EB1CA154B9D732');
        $this->addSql('DROP INDEX IDX_F4EB1CA154B9D732 ON map_place');
        $this->addSql('ALTER TABLE map_place DROP icon_id');
        $this->addSql('DROP TABLE map_icon');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Отдельный раздел иконок карты удалён намеренно.');
    }
}
