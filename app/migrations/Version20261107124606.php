<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261107124606 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE order_items (id INT AUTO_INCREMENT NOT NULL, product_key VARCHAR(60) NOT NULL, product_name VARCHAR(120) NOT NULL, unit_price NUMERIC(10, 2) NOT NULL, quantity INT NOT NULL, line_total NUMERIC(10, 2) NOT NULL, order_id INT NOT NULL, INDEX IDX_62809DB08D9F6D38 (order_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE orders (id INT AUTO_INCREMENT NOT NULL, customer_name VARCHAR(120) NOT NULL, email VARCHAR(190) NOT NULL, phone VARCHAR(40) NOT NULL, address LONGTEXT NOT NULL, order_type VARCHAR(20) NOT NULL, payment_method VARCHAR(40) NOT NULL, subtotal NUMERIC(10, 2) NOT NULL, delivery_fee NUMERIC(10, 2) NOT NULL, total NUMERIC(10, 2) NOT NULL, notes LONGTEXT DEFAULT NULL, status VARCHAR(30) NOT NULL, rider_latitude NUMERIC(10, 7) DEFAULT NULL, rider_longitude NUMERIC(10, 7) DEFAULT NULL, rider_last_update DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, user_id INT DEFAULT NULL, INDEX IDX_E52FFDEEA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE order_items ADD CONSTRAINT FK_62809DB08D9F6D38 FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEEA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE order_items DROP FOREIGN KEY FK_62809DB08D9F6D38');
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEEA76ED395');
        $this->addSql('DROP TABLE order_items');
        $this->addSql('DROP TABLE orders');
    }
}
