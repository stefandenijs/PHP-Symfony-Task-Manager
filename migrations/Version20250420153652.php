<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250420153652 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE task_tag (task_id UUID NOT NULL, tag_id UUID NOT NULL, PRIMARY KEY(task_id, tag_id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_6C0B4F048DB60186 ON task_tag (task_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_6C0B4F04BAD26311 ON task_tag (tag_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN task_tag.task_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN task_tag.tag_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE task_tag ADD CONSTRAINT FK_6C0B4F048DB60186 FOREIGN KEY (task_id) REFERENCES task (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE task_tag ADD CONSTRAINT FK_6C0B4F04BAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE task_tag DROP CONSTRAINT FK_6C0B4F048DB60186
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE task_tag DROP CONSTRAINT FK_6C0B4F04BAD26311
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE task_tag
        SQL);
    }
}
