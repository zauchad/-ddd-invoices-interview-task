<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Models;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final class InvoiceProductLine
{
    private function __construct(
        private readonly UuidInterface $id,
        private readonly Invoice $invoice,
        private readonly string $name,
        private readonly int $quantity,
        private readonly int $price
    ) {}

    public static function create(Invoice $invoice, string $name, int $quantity, int $price): self
    {
        return new self(Uuid::uuid4(), $invoice, $name, $quantity, $price);
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getInvoice(): Invoice
    {
        return $this->invoice;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    public function getTotalPrice(): int
    {
        return $this->quantity * $this->price;
    }
}
