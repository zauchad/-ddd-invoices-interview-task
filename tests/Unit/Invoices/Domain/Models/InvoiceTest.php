<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Domain\Models;

use Exception;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\Models\Invoice;
use Modules\Invoices\Domain\Models\InvoiceProductLine;
use PHPUnit\Framework\TestCase;

class InvoiceTest extends TestCase
{
    public function test_mark_as_sending_transitions_status(): void
    {
        $invoice = new Invoice(['status' => StatusEnum::Draft]);
        $invoice->setRelation('productLines', collect([
            new InvoiceProductLine(['quantity' => 1, 'price' => 100])
        ]));

        $invoice->markAsSending();

        $this->assertEquals(StatusEnum::Sending, $invoice->status);
    }

    public function test_mark_as_sending_throws_if_not_draft(): void
    {
        $invoice = new Invoice(['status' => StatusEnum::SentToClient]);
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Invoice must be in draft status to be sent");

        $invoice->markAsSending();
    }

    public function test_mark_as_sending_throws_if_no_lines(): void
    {
        $invoice = new Invoice(['status' => StatusEnum::Draft]);
        $invoice->setRelation('productLines', collect([]));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Invoice must have product lines to be sent");

        $invoice->markAsSending();
    }

    public function test_mark_as_sending_throws_if_invalid_lines(): void
    {
        $invoice = new Invoice(['status' => StatusEnum::Draft]);
        $invoice->setRelation('productLines', collect([
            new InvoiceProductLine(['quantity' => 0, 'price' => 100])
        ]));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("All product lines must have positive quantity and price");

        $invoice->markAsSending();
    }
}

