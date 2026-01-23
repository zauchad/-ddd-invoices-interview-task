<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230202173650 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create invoices and invoice_product_lines tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE invoices (
            id UUID NOT NULL,
            customer_name VARCHAR(255) NOT NULL,
            customer_email VARCHAR(255) NOT NULL,
            status VARCHAR(50) NOT NULL,
            PRIMARY KEY(id)
        )');

        $this->addSql('CREATE TABLE invoice_product_lines (
            id UUID NOT NULL,
            invoice_id UUID NOT NULL,
            name VARCHAR(255) NOT NULL,
            quantity INT NOT NULL,
            price INT NOT NULL,
            PRIMARY KEY(id),
            CONSTRAINT FK_invoice_product_lines_invoice 
                FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
        )');

        $this->addSql('CREATE INDEX IDX_invoice_product_lines_invoice ON invoice_product_lines (invoice_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE invoice_product_lines');
        $this->addSql('DROP TABLE invoices');
    }
}
