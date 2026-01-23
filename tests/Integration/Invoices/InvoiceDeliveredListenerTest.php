<?php

declare(strict_types=1);

namespace Tests\Integration\Invoices;

use Doctrine\ORM\EntityManagerInterface;
use Modules\Invoices\Application\Listeners\InvoiceDeliveredListener;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\Models\Invoice;
use Modules\Invoices\Domain\Repositories\InvoiceRepositoryInterface;
use Modules\Notifications\Api\Events\ResourceDeliveredEvent;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class InvoiceDeliveredListenerTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private InvoiceRepositoryInterface $repository;
    private InvoiceDeliveredListener $listener;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->repository = $container->get(InvoiceRepositoryInterface::class);
        $this->listener = $container->get(InvoiceDeliveredListener::class);
    }

    #[Test]
    public function transitions_sending_invoice_to_sent(): void
    {
        $invoice = Invoice::create('John', 'john@example.com');
        $invoice->addProductLine('Item', 1, 100);
        $invoice->markAsSending();
        $this->repository->save($invoice);

        $event = new ResourceDeliveredEvent($invoice->getId());
        ($this->listener)($event);

        $this->em->clear();
        $found = $this->repository->find($invoice->getId()->toString());

        self::assertEquals(StatusEnum::SentToClient, $found->getStatus());
    }

    #[Test]
    public function ignores_non_sending_invoice(): void
    {
        $invoice = Invoice::create('John', 'john@example.com');
        $this->repository->save($invoice);

        $event = new ResourceDeliveredEvent($invoice->getId());
        ($this->listener)($event);

        $this->em->clear();
        $found = $this->repository->find($invoice->getId()->toString());

        self::assertEquals(StatusEnum::Draft, $found->getStatus());
    }
}
