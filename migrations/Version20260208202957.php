<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260208202957 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
//        $this->addSql('ALTER TABLE part_request ADD default_vehicle_id INT DEFAULT NULL');
//        $this->addSql('ALTER TABLE part_request ADD CONSTRAINT FK_BBCB78941C7043B2 FOREIGN KEY (default_vehicle_id) REFERENCES vehicle (id) ON DELETE SET NULL NOT DEFERRABLE');
//        $this->addSql('CREATE INDEX IDX_BBCB78941C7043B2 ON part_request (default_vehicle_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
//        $this->addSql('ALTER TABLE part_request DROP CONSTRAINT FK_BBCB78941C7043B2');
//        $this->addSql('DROP INDEX IDX_BBCB78941C7043B2');
//        $this->addSql('ALTER TABLE part_request DROP default_vehicle_id');
    }
}
