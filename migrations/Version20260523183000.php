<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260523183000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add vehicle detail fields used by the admin vehicle detail page.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
DO $$
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_name = 'vehicle' AND column_name = 'current_odometer_rm'
    ) AND NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_name = 'vehicle' AND column_name = 'current_odometer_km'
    ) THEN
        ALTER TABLE vehicle RENAME COLUMN current_odometer_rm TO current_odometer_km;
    END IF;
END $$;
SQL);

        $this->addSql(<<<'SQL'
ALTER TABLE vehicle
    ADD COLUMN IF NOT EXISTS engine_number VARCHAR(64) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS factory_number VARCHAR(64) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS support_service VARCHAR(120) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS quality_category VARCHAR(80) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS operation_group VARCHAR(80) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS initial_cost NUMERIC(12, 2) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS passport_form VARCHAR(160) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS technical_certificate VARCHAR(160) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS commissioning_order VARCHAR(160) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS annual_mileage_km INT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS receipt_date DATE DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS received_from VARCHAR(160) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS supplier_id INT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS receiving_documents TEXT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS sent_to VARCHAR(160) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS battery_required INT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS battery_available INT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS tires_required INT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS tires_available INT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS maintenance_interval_km INT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS last_maintenance_date DATE DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS registration_number VARCHAR(32) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS dimensions VARCHAR(80) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS gross_weight_kg INT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS curb_weight_kg INT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS fuel_consumption_rates TEXT DEFAULT NULL
SQL);

        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_1B80E4862ADD6D8C ON vehicle (supplier_id)');

        $this->addSql(<<<'SQL'
DO $$
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'supplier')
       AND NOT EXISTS (
           SELECT 1
           FROM pg_constraint c
           JOIN pg_class t ON t.oid = c.conrelid
           WHERE c.conname = 'fk_1b80e4862add6d8c'
             AND t.relname = 'vehicle'
       ) THEN
        ALTER TABLE vehicle
            ADD CONSTRAINT FK_1B80E4862ADD6D8C
            FOREIGN KEY (supplier_id) REFERENCES supplier (id)
            ON DELETE SET NULL;
    END IF;
END $$;
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE vehicle DROP CONSTRAINT IF EXISTS FK_1B80E4862ADD6D8C');
        $this->addSql('DROP INDEX IF EXISTS IDX_1B80E4862ADD6D8C');
        $this->addSql(<<<'SQL'
ALTER TABLE vehicle
    DROP COLUMN IF EXISTS engine_number,
    DROP COLUMN IF EXISTS factory_number,
    DROP COLUMN IF EXISTS support_service,
    DROP COLUMN IF EXISTS quality_category,
    DROP COLUMN IF EXISTS operation_group,
    DROP COLUMN IF EXISTS initial_cost,
    DROP COLUMN IF EXISTS passport_form,
    DROP COLUMN IF EXISTS technical_certificate,
    DROP COLUMN IF EXISTS commissioning_order,
    DROP COLUMN IF EXISTS annual_mileage_km,
    DROP COLUMN IF EXISTS receipt_date,
    DROP COLUMN IF EXISTS received_from,
    DROP COLUMN IF EXISTS supplier_id,
    DROP COLUMN IF EXISTS receiving_documents,
    DROP COLUMN IF EXISTS sent_to,
    DROP COLUMN IF EXISTS battery_required,
    DROP COLUMN IF EXISTS battery_available,
    DROP COLUMN IF EXISTS tires_required,
    DROP COLUMN IF EXISTS tires_available,
    DROP COLUMN IF EXISTS maintenance_interval_km,
    DROP COLUMN IF EXISTS last_maintenance_date,
    DROP COLUMN IF EXISTS registration_number,
    DROP COLUMN IF EXISTS dimensions,
    DROP COLUMN IF EXISTS gross_weight_kg,
    DROP COLUMN IF EXISTS curb_weight_kg,
    DROP COLUMN IF EXISTS fuel_consumption_rates
SQL);
    }
}
