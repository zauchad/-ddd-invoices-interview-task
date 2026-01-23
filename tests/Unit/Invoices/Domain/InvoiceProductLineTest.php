<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Domain;

use Modules\Invoices\Domain\Models\Invoice;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class InvoiceProductLineTest extends TestCase
{
    #[Test]
    public function calculates_total_price(): void
    {
        $invoice = Invoice::create('John', 'john@example.com');
        $invoice->addProductLine('Product', 5, 200);

        $line = $invoice->getProductLines()[0];

        self::assertEquals(1000, $line->getTotalPrice());
        self::assertEquals(5, $line->getQuantity());
        self::assertEquals(200, $line->getPrice());
    }
}
