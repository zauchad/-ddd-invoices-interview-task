<?php

declare(strict_types=1);

namespace Modules\Invoices\Infrastructure\Repositories;

use Doctrine\ORM\EntityManagerInterface;
use Modules\Invoices\Domain\Models\Invoice;
use Modules\Invoices\Domain\Repositories\InvoiceRepositoryInterface;
use Ramsey\Uuid\UuidInterface;

/**
 * Doctrine implementation of InvoiceRepository.
 * 
 * Infrastructure concern: Handles persistence of pure domain entities
 * using Doctrine ORM without the domain having any knowledge of it.
 */
class DoctrineInvoiceRepository implements InvoiceRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {}

    public function find(string $id): ?Invoice
    {
        return $this->entityManager->find(Invoice::class, $id);
    }

    public function findByUuid(UuidInterface $id): ?Invoice
    {
        return $this->entityManager->find(Invoice::class, $id);
    }

    public function save(Invoice $invoice): void
    {
        $this->entityManager->persist($invoice);
        $this->entityManager->flush();
    }
}
