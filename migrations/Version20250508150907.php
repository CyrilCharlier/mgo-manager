<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250508150907 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE compte (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, name VARCHAR(50) NOT NULL, mgo VARCHAR(20) DEFAULT NULL, INDEX IDX_CFF65260A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE compte ADD CONSTRAINT FK_CFF65260A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE carte_obtenue DROP FOREIGN KEY FK_D9151609A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_D9151609A76ED395 ON carte_obtenue
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE carte_obtenue CHANGE user_id compte_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE carte_obtenue ADD CONSTRAINT FK_D9151609F2C56620 FOREIGN KEY (compte_id) REFERENCES compte (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_D9151609F2C56620 ON carte_obtenue (compte_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE carte_obtenue DROP FOREIGN KEY FK_D9151609F2C56620
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE compte DROP FOREIGN KEY FK_CFF65260A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE compte
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_D9151609F2C56620 ON carte_obtenue
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE carte_obtenue CHANGE compte_id user_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE carte_obtenue ADD CONSTRAINT FK_D9151609A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_D9151609A76ED395 ON carte_obtenue (user_id)
        SQL);
    }
}
