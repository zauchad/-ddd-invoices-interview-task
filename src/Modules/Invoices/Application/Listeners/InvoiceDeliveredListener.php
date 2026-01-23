<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\Listeners;

use Modules\Invoices\Domain\Repositories\InvoiceRepositoryInterface;
use Modules\Notifications\Api\Events\ResourceDeliveredEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: ResourceDeliveredEvent::class)]
final readonly class InvoiceDeliveredListener
{
    public function __construct(
        private InvoiceRepositoryInterface $invoiceRepository
    ) {}

    public function __invoke(ResourceDeliveredEvent $event): void
    {
        $invoice = $this->invoiceRepository->find($event->resourceId->toString());

        if ($invoice) {
            $invoice->markAsSentToClient();
            $this->invoiceRepository->save($invoice);
        }
    }
}
