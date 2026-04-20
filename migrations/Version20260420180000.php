<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260420180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix refresh_tokens id column: add auto-increment sequence for PostgreSQL after gesdinet v2 migration';
    }

    public function up(Schema $schema): void
    {
        // Convert id column from text to integer
        $this->addSql('ALTER TABLE refresh_tokens ALTER COLUMN id TYPE INTEGER USING id::integer');
        // Create and attach auto-increment sequence
        $this->addSql('CREATE SEQUENCE refresh_tokens_id_seq');
        $this->addSql("SELECT setval('refresh_tokens_id_seq', COALESCE((SELECT MAX(id) FROM refresh_tokens), 0) + 1, false)");
        $this->addSql("ALTER TABLE refresh_tokens ALTER COLUMN id SET DEFAULT nextval('refresh_tokens_id_seq')");
        $this->addSql("ALTER SEQUENCE refresh_tokens_id_seq OWNED BY refresh_tokens.id");
        $this->addSql("ALTER TABLE refresh_tokens ALTER COLUMN id SET NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE refresh_tokens ALTER COLUMN id DROP DEFAULT");
        $this->addSql('DROP SEQUENCE IF EXISTS refresh_tokens_id_seq');
        $this->addSql('ALTER TABLE refresh_tokens ALTER COLUMN id TYPE TEXT USING id::text');
    }
}
