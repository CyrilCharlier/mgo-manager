<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250508081015 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE carte (id INT AUTO_INCREMENT NOT NULL, s_id INT NOT NULL, name VARCHAR(50) NOT NULL, nbetoile INT NOT NULL, num INT NOT NULL, INDEX IDX_BAD4FFFDC1CECC4C (s_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE carte ADD CONSTRAINT FK_BAD4FFFDC1CECC4C FOREIGN KEY (s_id) REFERENCES `set` (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE carte DROP FOREIGN KEY FK_BAD4FFFDC1CECC4C
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE carte
        SQL);
    }
}
