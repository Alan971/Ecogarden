<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240920182058 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE info_user (id INT AUTO_INCREMENT NOT NULL, user_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, zip_code INT DEFAULT NULL, city VARCHAR(255) DEFAULT NULL, country VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_D4F804C7A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE info_user ADD CONSTRAINT FK_D4F804C7A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE data_user DROP FOREIGN KEY FK_36DC1DABA832C1C9');
        $this->addSql('DROP INDEX uniq_36dc1daba832c1c9 ON data_user');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_36DC1DABA76ED395 ON data_user (user_id)');
        $this->addSql('ALTER TABLE data_user ADD CONSTRAINT FK_36DC1DABA832C1C9 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE info_user DROP FOREIGN KEY FK_D4F804C7A76ED395');
        $this->addSql('DROP TABLE info_user');
        $this->addSql('ALTER TABLE data_user DROP FOREIGN KEY FK_36DC1DABA76ED395');
        $this->addSql('DROP INDEX uniq_36dc1daba76ed395 ON data_user');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_36DC1DABA832C1C9 ON data_user (user_id)');
        $this->addSql('ALTER TABLE data_user ADD CONSTRAINT FK_36DC1DABA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }
}
