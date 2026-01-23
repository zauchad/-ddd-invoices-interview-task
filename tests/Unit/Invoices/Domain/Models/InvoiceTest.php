<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Domain\Models;

use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\Exceptions\InvalidInvoiceStateException;
use Modules\Invoices\Domain\Exceptions\InvoiceValidationException;
use Modules\Invoices\Domain\Models\Invoice;
use PHPUnit\Framework\TestCase;

final class InvoiceTest extends TestCase
{
    public function test_create_invoice_starts_in_draft_status(): void
    {
        $invoice = Invoice::create('John Doe', 'john@example.com');

        $this->assertEquals(StatusEnum::Draft, $invoice->getStatus());
        $this->assertEquals('John Doe', $invoice->getCustomerName());
        $this->assertEquals('john@example.com', $invoice->getCustomerEmail());
        $this->assertEmpty($invoice->getProductLines());
    }

    public function test_add_product_line(): void
    {
        $invoice = Invoice::create('John Doe', 'john@example.com');
        $invoice->addProductLine('Product A', 2, 100);
        
        $this->assertCount(1, $invoice->getProductLines());
        $this->assertEquals('Product A', $invoice->getProductLines()[0]->getName());
        $this->assertEquals(2, $invoice->getProductLines()[0]->getQuantity());
        $this->assertEquals(100, $invoice->getProductLines()[0]->getPrice());
    }

    public function test_total_price_calculation(): void
    {
        $invoice = Invoice::create('John Doe', 'john@example.com');
        $invoice->addProductLine('Product A', 2, 100);
        $invoice->addProductLine('Product B', 3, 50);
        
        $this->assertEquals(350, $invoice->getTotalPrice());
    }

    public function test_mark_as_sending_transitions_status(): void
    {
        $invoice = Invoice::create('John Doe', 'john@example.com');
        $invoice->addProductLine('Product A', 1, 100);

        $invoice->markAsSending();

        $this->assertEquals(StatusEnum::Sending, $invoice->getStatus());
    }

    public function test_mark_as_sending_throws_if_not_draft(): void
    {
        $invoice = Invoice::create('John Doe', 'john@example.com');
        $invoice->addProductLine('Product A', 1, 100);
        $invoice->markAsSending();
        
        $this->expectException(InvalidInvoiceStateException::class);
        $invoice->markAsSending();
    }

    public function test_mark_as_sending_throws_if_no_lines(): void
    {
        $invoice = Invoice::create('John Doe', 'john@example.com');

        $this->expectException(InvoiceValidationException::class);
        $invoice->markAsSending();
    }

    public function test_mark_as_sending_throws_if_invalid_lines(): void
    {
        $invoice = Invoice::create('John Doe', 'john@example.com');
        $invoice->addProductLine('Product A', 0, 100);

        $this->expectException(InvoiceValidationException::class);
        $invoice->markAsSending();
    }

    public function test_mark_as_sent_to_client_transitions_from_sending(): void
    {
        $invoice = Invoice::create('John Doe', 'john@example.com');
        $invoice->addProductLine('Product A', 1, 100);
        $invoice->markAsSending();

        $invoice->markAsSentToClient();

        $this->assertEquals(StatusEnum::SentToClient, $invoice->getStatus());
    }

    public function test_mark_as_sent_to_client_is_idempotent_for_non_sending(): void
    {
        $invoice = Invoice::create('John Doe', 'john@example.com');
        $originalStatus = $invoice->getStatus();

        $invoice->markAsSentToClient();

        $this->assertEquals($originalStatus, $invoice->getStatus());
    }
}
