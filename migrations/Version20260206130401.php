<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260206130401 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX uniq_va_active_vehicle');
        $this->addSql("CREATE UNIQUE INDEX uniq_va_active_vehicle ON vehicle_assignment (vehicle_id) WHERE is_active = true");

    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE UNIQUE INDEX uniq_va_active_vehicle ON vehicle_assignment (vehicle_id) WHERE (is_active = true)');
        $this->addSql("DROP INDEX IF EXISTS uniq_va_active_vehicle");

    }
}
