<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Domain;

use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\Exceptions\InvalidInvoiceStateException;
use Modules\Invoices\Domain\Exceptions\InvoiceValidationException;
use Modules\Invoices\Domain\Models\Invoice;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class InvoiceTest extends TestCase
{
    #[Test]
    public function creates_invoice_in_draft_status(): void
    {
        $invoice = Invoice::create('John Doe', 'john@example.com');

        self::assertEquals(StatusEnum::Draft, $invoice->getStatus());
        self::assertEquals('John Doe', $invoice->getCustomerName());
        self::assertEquals('john@example.com', $invoice->getCustomerEmail());
        self::assertEmpty($invoice->getProductLines());
    }

    #[Test]
    public function adds_product_line(): void
    {
        $invoice = Invoice::create('John Doe', 'john@example.com');
        
        $invoice->addProductLine('Product A', 2, 100);
        
        self::assertCount(1, $invoice->getProductLines());
        self::assertEquals('Product A', $invoice->getProductLines()[0]->getName());
        self::assertEquals(2, $invoice->getProductLines()[0]->getQuantity());
        self::assertEquals(100, $invoice->getProductLines()[0]->getPrice());
    }

    #[Test]
    public function calculates_total_price(): void
    {
        $invoice = Invoice::create('John Doe', 'john@example.com');
        $invoice->addProductLine('Product A', 2, 100);
        $invoice->addProductLine('Product B', 3, 50);
        
        self::assertEquals(350, $invoice->getTotalPrice());
    }

    #[Test]
    public function transitions_to_sending(): void
    {
        $invoice = $this->createValidInvoice();

        $invoice->markAsSending();

        self::assertEquals(StatusEnum::Sending, $invoice->getStatus());
    }

    #[Test]
    public function prevents_sending_non_draft_invoice(): void
    {
        $invoice = $this->createValidInvoice();
        $invoice->markAsSending();
        
        $this->expectException(InvalidInvoiceStateException::class);
        $invoice->markAsSending();
    }

    #[Test]
    public function prevents_sending_empty_invoice(): void
    {
        $invoice = Invoice::create('John Doe', 'john@example.com');

        $this->expectException(InvoiceValidationException::class);
        $invoice->markAsSending();
    }

    #[Test]
    public function prevents_sending_invoice_with_invalid_lines(): void
    {
        $invoice = Invoice::create('John Doe', 'john@example.com');
        $invoice->addProductLine('Product A', 0, 100);

        $this->expectException(InvoiceValidationException::class);
        $invoice->markAsSending();
    }

    #[Test]
    public function transitions_to_sent_from_sending(): void
    {
        $invoice = $this->createValidInvoice();
        $invoice->markAsSending();

        $invoice->markAsSentToClient();

        self::assertEquals(StatusEnum::SentToClient, $invoice->getStatus());
    }

    #[Test]
    public function ignores_sent_transition_for_non_sending(): void
    {
        $invoice = Invoice::create('John Doe', 'john@example.com');
        $originalStatus = $invoice->getStatus();

        $invoice->markAsSentToClient();

        self::assertEquals($originalStatus, $invoice->getStatus());
    }

    private function createValidInvoice(): Invoice
    {
        $invoice = Invoice::create('John Doe', 'john@example.com');
        $invoice->addProductLine('Product A', 1, 100);

        return $invoice;
    }
}
