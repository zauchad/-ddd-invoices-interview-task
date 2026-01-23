<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\Services;

use Modules\Invoices\Api\Dtos\CreateInvoiceDto;
use Modules\Invoices\Domain\Exceptions\InvoiceNotFoundException;
use Modules\Invoices\Domain\Models\Invoice;
use Modules\Invoices\Domain\Repositories\InvoiceRepositoryInterface;
use Modules\Notifications\Api\Dtos\NotifyData;
use Modules\Notifications\Api\NotificationFacadeInterface;
use Ramsey\Uuid\Uuid;

/**
 * Application Service: Orchestrates use cases without containing business logic.
 * 
 * Coordinates between:
 * - Domain layer (Invoice aggregate)
 * - Infrastructure (Repository for persistence)
 * - Other modules (NotificationFacade for cross-module communication)
 */
class InvoiceService
{
    public function __construct(
        private readonly InvoiceRepositoryInterface $invoiceRepository,
        private readonly NotificationFacadeInterface $notificationFacade
    ) {}

    public function createInvoice(CreateInvoiceDto $data): Invoice
    {
        // Use domain factory method
        $invoice = Invoice::create(
            customerName: $data->customerName,
            customerEmail: $data->customerEmail
        );

        // Add product lines through aggregate root
        foreach ($data->productLines as $lineDto) {
            $invoice->addProductLine(
                name: $lineDto->name,
                quantity: $lineDto->quantity,
                price: $lineDto->price
            );
        }

        // Delegate persistence to repository
        $this->invoiceRepository->save($invoice);

        return $invoice;
    }

    public function getInvoice(string $id): ?Invoice
    {
        return $this->invoiceRepository->find($id);
    }

    /**
     * @throws InvoiceNotFoundException
     * @throws \Modules\Invoices\Domain\Exceptions\InvalidInvoiceStateException
     * @throws \Modules\Invoices\Domain\Exceptions\InvoiceValidationException
     */
    public function sendInvoice(string $id): void
    {
        $invoice = $this->invoiceRepository->find($id);

        if (!$invoice) {
            throw InvoiceNotFoundException::withId($id);
        }

        // 1. Enforce Domain Rules & State Transition
        $invoice->markAsSending();
        
        // 2. Persist Change
        $this->invoiceRepository->save($invoice);

        // 3. Handle Side Effects (notify other module)
        $this->notificationFacade->notify(new NotifyData(
            resourceId: $invoice->getId(),
            toEmail: $invoice->getCustomerEmail(),
            subject: "Invoice for " . $invoice->getCustomerName(),
            message: "Please find your invoice attached."
        ));
    }
}
