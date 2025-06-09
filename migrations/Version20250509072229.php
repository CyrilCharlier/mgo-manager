<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250509072229 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE carte_double DROP FOREIGN KEY FK_CC0392DEC9C7CEB6
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE carte_double DROP FOREIGN KEY FK_CC0392DEF2C56620
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE carte_double
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE carte_obtenue ADD nombre INT NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE carte_double (id INT AUTO_INCREMENT NOT NULL, compte_id INT NOT NULL, carte_id INT NOT NULL, nombre INT NOT NULL, INDEX IDX_CC0392DEF2C56620 (compte_id), INDEX IDX_CC0392DEC9C7CEB6 (carte_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE carte_double ADD CONSTRAINT FK_CC0392DEC9C7CEB6 FOREIGN KEY (carte_id) REFERENCES carte (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE carte_double ADD CONSTRAINT FK_CC0392DEF2C56620 FOREIGN KEY (compte_id) REFERENCES compte (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE carte_obtenue DROP nombre
        SQL);
    }
}
