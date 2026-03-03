<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260121220808 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add status and closed_at to service_event (backfill existing rows)';
    }

    public function up(Schema $schema): void
    {
        // 1) add nullable first (so existing rows don't fail)
        $this->addSql("ALTER TABLE service_event ADD status VARCHAR(10) DEFAULT NULL");
        $this->addSql("ALTER TABLE service_event ADD closed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL");

        // 2) backfill existing rows
        $this->addSql("UPDATE service_event SET status = 'OPEN' WHERE status IS NULL");

        // 3) enforce NOT NULL + set DB default for future inserts
        $this->addSql("ALTER TABLE service_event ALTER COLUMN status SET NOT NULL");
        $this->addSql("ALTER TABLE service_event ALTER COLUMN status SET DEFAULT 'OPEN'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE service_event DROP COLUMN closed_at');
        $this->addSql('ALTER TABLE service_event DROP COLUMN status');
    }
}
