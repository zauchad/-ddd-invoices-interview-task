<?php

declare(strict_types=1);

namespace Tests\Integration\Invoices;

use Doctrine\ORM\EntityManagerInterface;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\Models\Invoice;
use Modules\Invoices\Infrastructure\Repositories\DoctrineInvoiceRepository;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DoctrineInvoiceRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private DoctrineInvoiceRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->repository = new DoctrineInvoiceRepository($this->em);
    }

    #[Test]
    public function persists_and_retrieves_invoice(): void
    {
        $invoice = Invoice::create('John Doe', 'john@example.com');
        $invoice->addProductLine('Product A', 2, 100);

        $this->repository->save($invoice);
        $this->em->clear();

        $found = $this->repository->find($invoice->getId()->toString());

        self::assertNotNull($found);
        self::assertEquals('John Doe', $found->getCustomerName());
        self::assertEquals(StatusEnum::Draft, $found->getStatus());
        self::assertCount(1, $found->getProductLines());
    }

    #[Test]
    public function returns_null_for_non_existent_invoice(): void
    {
        $found = $this->repository->find('00000000-0000-0000-0000-000000000000');

        self::assertNull($found);
    }

    #[Test]
    public function persists_status_changes(): void
    {
        $invoice = Invoice::create('Jane Doe', 'jane@example.com');
        $invoice->addProductLine('Service', 1, 500);
        $this->repository->save($invoice);

        $invoice->markAsSending();
        $this->repository->save($invoice);
        $this->em->clear();

        $found = $this->repository->find($invoice->getId()->toString());

        self::assertEquals(StatusEnum::Sending, $found->getStatus());
    }
}
