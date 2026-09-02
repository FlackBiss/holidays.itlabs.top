<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260827160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Recalculates route_drawn from active map roads for existing infrastructure objects';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            UPDATE map_place p
            SET p.route_drawn = CASE WHEN EXISTS (
                SELECT 1
                FROM map_edge e
                WHERE e.plan_id = p.plan_id
                  AND e.active = 1
                  AND (e.from_node_id = p.node_id OR e.to_node_id = p.node_id)
            ) THEN 1 ELSE 0 END
            WHERE p.type = 'infrastructure'
            SQL);
    }

    public function down(Schema $schema): void
    {
        // Data synchronization cannot be reversed reliably.
    }
}
