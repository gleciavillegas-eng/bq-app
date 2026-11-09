<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261109025907 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contact_messages ADD user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE contact_messages ADD CONSTRAINT FK_41278201A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_41278201A76ED395 ON contact_messages (user_id)');
        $this->addSql('ALTER TABLE order_items ADD product_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE order_items ADD CONSTRAINT FK_62809DB04584665A FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_62809DB04584665A ON order_items (product_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contact_messages DROP FOREIGN KEY FK_41278201A76ED395');
        $this->addSql('DROP INDEX IDX_41278201A76ED395 ON contact_messages');
        $this->addSql('ALTER TABLE contact_messages DROP user_id');
        $this->addSql('ALTER TABLE order_items DROP FOREIGN KEY FK_62809DB04584665A');
        $this->addSql('DROP INDEX IDX_62809DB04584665A ON order_items');
        $this->addSql('ALTER TABLE order_items DROP product_id');
    }
}
