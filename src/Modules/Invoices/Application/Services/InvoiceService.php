<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\Services;

use Exception;
use Ramsey\Uuid\Uuid;
use Modules\Invoices\Api\Dtos\CreateInvoiceDto;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\Models\Invoice;
use Modules\Invoices\Domain\Models\InvoiceProductLine;
use Modules\Invoices\Domain\Repositories\InvoiceRepositoryInterface;
use Modules\Notifications\Api\Dtos\NotifyData;
use Modules\Notifications\Api\NotificationFacadeInterface;

class InvoiceService
{
    public function __construct(
        private readonly InvoiceRepositoryInterface $invoiceRepository,
        private readonly NotificationFacadeInterface $notificationFacade
    ) {}

    public function createInvoice(CreateInvoiceDto $data): Invoice
    {
        // Initialize the aggregate root
        $invoice = new Invoice([
            'customer_name' => $data->customerName,
            'customer_email' => $data->customerEmail,
            'status' => StatusEnum::Draft,
        ]);

        // Persist root first (needed for ID generation in standard Eloquent flow)
        $this->invoiceRepository->save($invoice);

        // Create line items efficiently
        $lines = array_map(
            fn ($lineDto) => new InvoiceProductLine([
                'name' => $lineDto->name,
                'quantity' => $lineDto->quantity,
                'price' => $lineDto->price,
            ]),
            $data->productLines
        );

        // Associate lines in memory so the returned object is complete
        $invoice->setRelation('productLines', collect($lines));
        
        // Delegate persistence of the relationship to the repository
        $this->invoiceRepository->save($invoice);

        return $invoice;
    }

    public function getInvoice(string $id): ?Invoice
    {
        return $this->invoiceRepository->find($id);
    }

    /** @throws Exception */
    public function sendInvoice(string $id): void
    {
        $invoice = $this->invoiceRepository->find($id);

        if (!$invoice) {
            throw new Exception("Invoice not found");
        }

        // 1. Enforce Domain Rules & State Transition
        // The Model validates invariants (Draft status, positive lines, etc.)
        $invoice->markAsSending();
        
        // 2. Persist State Change
        $this->invoiceRepository->save($invoice);

        // 3. Handle Side Effects (Notification)
        $this->notificationFacade->notify(new NotifyData(
            resourceId: Uuid::fromString($invoice->id),
            toEmail: $invoice->customer_email,
            subject: "Invoice for " . $invoice->customer_name,
            message: "Please find your invoice attached."
        ));
    }
}
