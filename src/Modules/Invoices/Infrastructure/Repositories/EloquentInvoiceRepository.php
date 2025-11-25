<?php

declare(strict_types=1);

namespace Modules\Invoices\Infrastructure\Repositories;

use Modules\Invoices\Domain\Models\Invoice;
use Modules\Invoices\Domain\Repositories\InvoiceRepositoryInterface;

class EloquentInvoiceRepository implements InvoiceRepositoryInterface
{
    public function find(string $id): ?Invoice
    {
        return Invoice::with('productLines')->find($id);
    }

    public function save(Invoice $invoice): void
    {
        $invoice->push();
    }
}

