<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\Listeners;

use Modules\Invoices\Domain\Repositories\InvoiceRepositoryInterface;
use Modules\Notifications\Api\Events\ResourceDeliveredEvent;

class InvoiceDeliveredListener
{
    public function __construct(
        private InvoiceRepositoryInterface $invoiceRepository
    ) {}

    public function handle(ResourceDeliveredEvent $event): void
    {
        $invoiceId = $event->resourceId->toString();
        $invoice = $this->invoiceRepository->find($invoiceId);

        if ($invoice) {
            // Delegate state transition logic to the Domain Model
            $invoice->markAsSentToClient();
            $this->invoiceRepository->save($invoice);
        }
    }
}
