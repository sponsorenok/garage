<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260523200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add vehicle passport document relation.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE vehicle ADD COLUMN IF NOT EXISTS passport_form_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_1B80E486E3D4F5A0 ON vehicle (passport_form_id)');
        $this->addSql(<<<'SQL'
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM pg_constraint c
        JOIN pg_class t ON t.oid = c.conrelid
        WHERE c.conname = 'fk_vehicle_passport_form'
          AND t.relname = 'vehicle'
    ) THEN
        ALTER TABLE vehicle
            ADD CONSTRAINT fk_vehicle_passport_form
            FOREIGN KEY (passport_form_id) REFERENCES doc (id)
            ON DELETE SET NULL;
    END IF;
END $$;
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE vehicle DROP CONSTRAINT IF EXISTS fk_vehicle_passport_form');
        $this->addSql('DROP INDEX IF EXISTS IDX_1B80E486E3D4F5A0');
        $this->addSql('ALTER TABLE vehicle DROP COLUMN IF EXISTS passport_form_id');
    }
}
