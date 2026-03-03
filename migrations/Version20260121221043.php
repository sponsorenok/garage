<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260121221043 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE service_event ADD COLUMN IF NOT EXISTS status VARCHAR(10)");
        $this->addSql("ALTER TABLE service_event ADD COLUMN IF NOT EXISTS closed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL");

        $this->addSql("UPDATE service_event SET status = 'OPEN' WHERE status IS NULL");
        $this->addSql("ALTER TABLE service_event ALTER COLUMN status SET NOT NULL");
        $this->addSql("ALTER TABLE service_event ALTER COLUMN status SET DEFAULT 'OPEN'");
    }


    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE service_event DROP status');
        $this->addSql('ALTER TABLE service_event DROP closed_at');
    }
}
