<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260122135744 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE service_plan ADD template_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE service_plan ADD CONSTRAINT FK_1319EF915DA0FB8 FOREIGN KEY (template_id) REFERENCES service_plan_template (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_1319EF915DA0FB8 ON service_plan (template_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE service_plan DROP CONSTRAINT FK_1319EF915DA0FB8');
        $this->addSql('DROP INDEX IDX_1319EF915DA0FB8');
        $this->addSql('ALTER TABLE service_plan DROP template_id');
    }
}
