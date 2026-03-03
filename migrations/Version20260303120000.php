<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260303120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Repair migration: restore uniq_va_active_vehicle partial unique index, ensure vehicle_type/type_id FK, ensure department.parent_id FK (PostgreSQL, idempotent).';
    }

    public function up(Schema $schema): void
    {
        // 0) Restore the critical partial unique index:
        // Only ONE active assignment per vehicle (WHERE is_active = true)
        $this->addSql(<<<'SQL'
DO $$
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'vehicle_assignment') THEN
        -- Create the index if missing (partial unique index)
        EXECUTE 'CREATE UNIQUE INDEX IF NOT EXISTS uniq_va_active_vehicle
                 ON vehicle_assignment (vehicle_id)
                 WHERE is_active = true';
    END IF;
END $$;
SQL);

        // 1) Ensure vehicle_type table exists
        $this->addSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS vehicle_type (
    id SERIAL NOT NULL,
    name VARCHAR(255) NOT NULL,
    PRIMARY KEY(id)
);
SQL);

        // 2) Ensure vehicle.type_id column exists
        $this->addSql(<<<'SQL'
DO $$
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'vehicle') THEN
        IF NOT EXISTS (
            SELECT 1 FROM information_schema.columns
            WHERE table_name='vehicle' AND column_name='type_id'
        ) THEN
            ALTER TABLE vehicle ADD COLUMN type_id INT DEFAULT NULL;
        END IF;
    END IF;
END $$;
SQL);

        // 3) Ensure FK vehicle(type_id) -> vehicle_type(id) exists
        // (No "IF NOT EXISTS" for constraints in PG, so we check pg_constraint)
        $this->addSql(<<<'SQL'
DO $$
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'vehicle')
       AND EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'vehicle_type') THEN

        IF NOT EXISTS (
            SELECT 1
            FROM pg_constraint c
            JOIN pg_class t ON t.oid = c.conrelid
            WHERE c.conname = 'fk_vehicle_type'
              AND t.relname = 'vehicle'
        ) THEN
            ALTER TABLE vehicle
                ADD CONSTRAINT fk_vehicle_type
                FOREIGN KEY (type_id) REFERENCES vehicle_type (id)
                ON DELETE SET NULL;
        END IF;

        -- Helpful index for FK lookups
        EXECUTE 'CREATE INDEX IF NOT EXISTS idx_vehicle_type_id ON vehicle (type_id)';
    END IF;
END $$;
SQL);

        // 4) Ensure department.parent_id exists
        $this->addSql(<<<'SQL'
DO $$
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'department') THEN
        IF NOT EXISTS (
            SELECT 1 FROM information_schema.columns
            WHERE table_name='department' AND column_name='parent_id'
        ) THEN
            ALTER TABLE department ADD COLUMN parent_id INT DEFAULT NULL;
        END IF;
    END IF;
END $$;
SQL);

        // 5) Ensure FK department(parent_id) -> department(id) exists
        $this->addSql(<<<'SQL'
DO $$
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'department') THEN

        IF NOT EXISTS (
            SELECT 1
            FROM pg_constraint c
            JOIN pg_class t ON t.oid = c.conrelid
            WHERE c.conname = 'fk_department_parent'
              AND t.relname = 'department'
        ) THEN
            ALTER TABLE department
                ADD CONSTRAINT fk_department_parent
                FOREIGN KEY (parent_id) REFERENCES department (id)
                ON DELETE SET NULL;
        END IF;

        EXECUTE 'CREATE INDEX IF NOT EXISTS idx_department_parent_id ON department (parent_id)';
    END IF;
END $$;
SQL);
    }

    public function down(Schema $schema): void
    {
        // Intentionally empty: repair migrations should not try to rollback in production.
    }
}
