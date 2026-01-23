<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\Listeners;

use Modules\Invoices\Domain\Repositories\InvoiceRepositoryInterface;
use Modules\Notifications\Api\Events\ResourceDeliveredEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: ResourceDeliveredEvent::class)]
class InvoiceDeliveredListener
{
    public function __construct(
        private readonly InvoiceRepositoryInterface $invoiceRepository
    ) {}

    public function __invoke(ResourceDeliveredEvent $event): void
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
