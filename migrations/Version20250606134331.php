<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250606134331 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create house and booking tables with correct structure';
    }

    public function up(Schema $schema): void
    {
        // Create house table
        $this->addSql('CREATE TABLE house (
            id SERIAL PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            price DOUBLE PRECISION NOT NULL,
            location VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            image VARCHAR(255) NOT NULL
        )');

        // Create booking table
        $this->addSql('CREATE TABLE booking (
            id SERIAL PRIMARY KEY,
            house_id INTEGER NOT NULL,
            phone VARCHAR(20) NOT NULL,
            message TEXT,
            created_at TIMESTAMP NOT NULL,
            CONSTRAINT fk_booking_house FOREIGN KEY (house_id) REFERENCES house (id) ON DELETE CASCADE
        )');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE booking');
        $this->addSql('DROP TABLE house');
    }
}
