<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250508092144 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE carte_obtenue (id INT AUTO_INCREMENT NOT NULL, carte_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_D9151609C9C7CEB6 (carte_id), INDEX IDX_D9151609A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE carte_obtenue ADD CONSTRAINT FK_D9151609C9C7CEB6 FOREIGN KEY (carte_id) REFERENCES carte (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE carte_obtenue ADD CONSTRAINT FK_D9151609A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE carte_obtenue DROP FOREIGN KEY FK_D9151609C9C7CEB6
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE carte_obtenue DROP FOREIGN KEY FK_D9151609A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE carte_obtenue
        SQL);
    }
}
