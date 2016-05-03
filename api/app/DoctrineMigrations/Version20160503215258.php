<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160503215258 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE event_dto CHANGE event_datetime event_datetime TIMESTAMP(6) WITHOUT TIME ZONE NOT NULL');
        $this->addSql('ALTER TABLE tide_dto CHANGE creation_date creation_date TIMESTAMP(6) WITHOUT TIME ZONE NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE event_dto CHANGE event_datetime event_datetime TIMESTAMP(6) WITHOUT TIME ZONE DEFAULT \'CURRENT_TIMESTAMP(6)\' NOT NULL');
        $this->addSql('ALTER TABLE tide_dto CHANGE creation_date creation_date TIMESTAMP(6) WITHOUT TIME ZONE DEFAULT \'CURRENT_TIMESTAMP(6)\' NOT NULL');
    }
}
