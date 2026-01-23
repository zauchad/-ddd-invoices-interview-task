<?php

declare(strict_types=1);

namespace Modules\Invoices\Infrastructure\Repositories;

use Doctrine\ORM\EntityManagerInterface;
use Modules\Invoices\Domain\Models\Invoice;
use Modules\Invoices\Domain\Repositories\InvoiceRepositoryInterface;

final readonly class DoctrineInvoiceRepository implements InvoiceRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function find(string $id): ?Invoice
    {
        return $this->entityManager->find(Invoice::class, $id);
    }

    public function save(Invoice $invoice): void
    {
        $this->entityManager->persist($invoice);
        $this->entityManager->flush();
    }
}
