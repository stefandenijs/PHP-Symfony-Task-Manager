<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250420111809 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE tag (id UUID NOT NULL, creator_id UUID NOT NULL, colour VARCHAR(255) NOT NULL, title VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_389B78361220EA6 ON tag (creator_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN tag.id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN tag.creator_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN tag.created_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN tag.updated_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE task (id UUID NOT NULL, owner_id UUID NOT NULL, parent_id UUID DEFAULT NULL, title VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, deadline TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, completed BOOLEAN NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_527EDB257E3C61F9 ON task (owner_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_527EDB25727ACA70 ON task (parent_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN task.id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN task.owner_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN task.parent_id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN task.created_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN task.updated_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE "user" (id UUID NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, username VARCHAR(100) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON "user" (email)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN "user".id IS '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN "user".created_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN "user".updated_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tag ADD CONSTRAINT FK_389B78361220EA6 FOREIGN KEY (creator_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE task ADD CONSTRAINT FK_527EDB257E3C61F9 FOREIGN KEY (owner_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE task ADD CONSTRAINT FK_527EDB25727ACA70 FOREIGN KEY (parent_id) REFERENCES task (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tag DROP CONSTRAINT FK_389B78361220EA6
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE task DROP CONSTRAINT FK_527EDB257E3C61F9
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE task DROP CONSTRAINT FK_527EDB25727ACA70
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE tag
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE task
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE "user"
        SQL);
    }
}
