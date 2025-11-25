<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\Services;

use Exception;
use Ramsey\Uuid\Uuid;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\Models\Invoice;
use Modules\Invoices\Domain\Models\InvoiceProductLine;
use Modules\Invoices\Domain\Repositories\InvoiceRepositoryInterface;
use Modules\Notifications\Api\Dtos\NotifyData;
use Modules\Notifications\Api\NotificationFacadeInterface;

class InvoiceService
{
    public function __construct(
        private InvoiceRepositoryInterface $invoiceRepository,
        private NotificationFacadeInterface $notificationFacade
    ) {}

    public function createInvoice(array $data): Invoice
    {
        $invoice = new Invoice([
            'customer_name' => $data['customer_name'],
            'customer_email' => $data['customer_email'],
            'status' => StatusEnum::Draft,
        ]);

        // Persist root first to get ID for lines (standard Eloquent flow)
        $this->invoiceRepository->save($invoice);

        $lines = [];
        foreach ($data['product_lines'] ?? [] as $lineData) {
            $lines[] = new InvoiceProductLine([
                'name' => $lineData['name'],
                'quantity' => $lineData['quantity'],
                'price' => $lineData['price'],
            ]);
        }

        // Associate and save lines
        $invoice->productLines()->saveMany($lines);

        // Reload relationship to ensure consistency in return value
        $invoice->load('productLines');

        return $invoice;
    }

    public function getInvoice(string $id): ?Invoice
    {
        return $this->invoiceRepository->find($id);
    }

    /**
     * @throws Exception
     */
    public function sendInvoice(string $id): void
    {
        $invoice = $this->invoiceRepository->find($id);

        if (!$invoice) {
            throw new Exception("Invoice not found");
        }

        // 1. Domain Logic: Check invariants and update state
        $invoice->markAsSending();
        
        // 2. Persist State Change
        $this->invoiceRepository->save($invoice);

        // 3. Side Effects (Notification)
        // Note: Ideally this should be an event (InvoiceSending) that a listener handles,
        // but calling the Facade here is acceptable for this scope.
        $this->notificationFacade->notify(new NotifyData(
            resourceId: Uuid::fromString($invoice->id),
            toEmail: $invoice->customer_email,
            subject: "Invoice for " . $invoice->customer_name,
            message: "Please find your invoice attached."
        ));
    }
}
