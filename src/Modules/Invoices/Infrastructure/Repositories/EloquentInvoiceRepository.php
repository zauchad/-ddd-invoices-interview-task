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
        // Save the root invoice to ensure it has an ID
        $invoice->save();

        // If product lines are loaded (e.g. new lines attached via setRelation), save them
        if ($invoice->relationLoaded('productLines')) {
            $invoice->productLines()->saveMany($invoice->productLines);
        }
    }
}
