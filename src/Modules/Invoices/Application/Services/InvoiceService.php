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
        $invoice = new Invoice([
            'customer_name' => $data->customerName,
            'customer_email' => $data->customerEmail,
            'status' => StatusEnum::Draft,
        ]);

        // Persist root first (needed for ID generation in standard Eloquent)
        $this->invoiceRepository->save($invoice);

        // Create lines efficiently using array_map
        $lines = array_map(
            fn ($lineDto) => new InvoiceProductLine([
                'name' => $lineDto->name,
                'quantity' => $lineDto->quantity,
                'price' => $lineDto->price,
            ]),
            $data->productLines
        );

        $invoice->productLines()->saveMany($lines);

        return $invoice->load('productLines');
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
        $invoice->markAsSending();
        
        // 2. Persist Change
        $this->invoiceRepository->save($invoice);

        // 3. Handle Side Effects
        $this->notificationFacade->notify(new NotifyData(
            resourceId: Uuid::fromString($invoice->id),
            toEmail: $invoice->customer_email,
            subject: "Invoice for " . $invoice->customer_name,
            message: "Please find your invoice attached."
        ));
    }
}
