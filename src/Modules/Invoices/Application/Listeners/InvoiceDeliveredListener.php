<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\Listeners;

use Modules\Invoices\Domain\Enums\StatusEnum;
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

        // Requirement: Only mark as sent-to-client if currently sending
        if ($invoice && $invoice->status === StatusEnum::Sending) {
            $invoice->status = StatusEnum::SentToClient;
            $this->invoiceRepository->save($invoice);
        }
    }
}

