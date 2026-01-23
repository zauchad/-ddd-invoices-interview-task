<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\Services;

use Modules\Invoices\Api\Dtos\CreateInvoiceDto;
use Modules\Invoices\Domain\Exceptions\InvoiceNotFoundException;
use Modules\Invoices\Domain\Models\Invoice;
use Modules\Invoices\Domain\Repositories\InvoiceRepositoryInterface;
use Modules\Notifications\Api\Dtos\NotifyData;
use Modules\Notifications\Api\NotificationFacadeInterface;

final readonly class InvoiceService
{
    public function __construct(
        private InvoiceRepositoryInterface $invoiceRepository,
        private NotificationFacadeInterface $notificationFacade
    ) {}

    public function createInvoice(CreateInvoiceDto $data): Invoice
    {
        $invoice = Invoice::create($data->customerName, $data->customerEmail);

        foreach ($data->productLines as $lineDto) {
            $invoice->addProductLine($lineDto->name, $lineDto->quantity, $lineDto->price);
        }

        $this->invoiceRepository->save($invoice);

        return $invoice;
    }

    public function getInvoice(string $id): ?Invoice
    {
        return $this->invoiceRepository->find($id);
    }

    public function sendInvoice(string $id): void
    {
        $invoice = $this->invoiceRepository->find($id)
            ?? throw InvoiceNotFoundException::withId($id);

        $invoice->markAsSending();
        $this->invoiceRepository->save($invoice);

        $this->notificationFacade->notify(new NotifyData(
            resourceId: $invoice->getId(),
            toEmail: $invoice->getCustomerEmail(),
            subject: "Invoice for {$invoice->getCustomerName()}",
            message: "Please find your invoice attached."
        ));
    }
}
