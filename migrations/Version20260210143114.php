<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260210143114 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
//        $this->addSql('ALTER TABLE purchase ALTER status DROP DEFAULT');
//        $this->addSql('ALTER TABLE purchase_line ADD source_type VARCHAR(20) DEFAULT \'MANUAL\' NOT NULL');
//        $this->addSql('ALTER TABLE purchase_line ADD request_item_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
//        $this->addSql('ALTER TABLE purchase ALTER status SET DEFAULT \'DRAFT\'');
//        $this->addSql('ALTER TABLE purchase_line DROP source_type');
//        $this->addSql('ALTER TABLE purchase_line DROP request_item_id');
    }
}
