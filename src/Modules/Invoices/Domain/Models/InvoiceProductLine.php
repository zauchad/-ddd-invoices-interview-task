<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Models;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Invoice Product Line - Pure Domain Entity.
 * 
 * This is a child entity belonging to the Invoice aggregate.
 * It has NO infrastructure dependencies.
 */
class InvoiceProductLine
{
    private UuidInterface $id;
    private Invoice $invoice;
    private string $name;
    private int $quantity;
    private int $price;

    private function __construct(
        UuidInterface $id,
        Invoice $invoice,
        string $name,
        int $quantity,
        int $price
    ) {
        $this->id = $id;
        $this->invoice = $invoice;
        $this->name = $name;
        $this->quantity = $quantity;
        $this->price = $price;
    }

    public static function create(Invoice $invoice, string $name, int $quantity, int $price): self
    {
        return new self(
            id: Uuid::uuid4(),
            invoice: $invoice,
            name: $name,
            quantity: $quantity,
            price: $price
        );
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
