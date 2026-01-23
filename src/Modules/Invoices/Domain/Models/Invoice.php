<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Models;

use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\Exceptions\InvalidInvoiceStateException;
use Modules\Invoices\Domain\Exceptions\InvoiceValidationException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Invoice Aggregate Root - Pure Domain Entity.
 * 
 * This entity has NO infrastructure dependencies (no Eloquent, no Doctrine base classes).
 * Persistence is handled externally by the Repository using Doctrine's reflection-based mapping.
 * 
 * Key DDD principle: The Domain layer has ZERO knowledge of how it's persisted.
 */
class Invoice
{
    private UuidInterface $id;
    private string $customerName;
    private string $customerEmail;
    private StatusEnum $status;
    
    /** @var array<InvoiceProductLine> */
    private array $productLines = [];

    private function __construct(
        UuidInterface $id,
        string $customerName,
        string $customerEmail,
        StatusEnum $status
    ) {
        $this->id = $id;
        $this->customerName = $customerName;
        $this->customerEmail = $customerEmail;
        $this->status = $status;
    }

    /**
     * Factory method: Creates a new draft invoice.
     */
    public static function create(string $customerName, string $customerEmail): self
    {
        return new self(
            id: Uuid::uuid4(),
            customerName: $customerName,
            customerEmail: $customerEmail,
            status: StatusEnum::Draft
        );
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getCustomerName(): string
    {
        return $this->customerName;
    }

    public function getCustomerEmail(): string
    {
        return $this->customerEmail;
    }

    public function getStatus(): StatusEnum
    {
        return $this->status;
    }

    /**
     * @return array<InvoiceProductLine>
     */
    public function getProductLines(): array
    {
        return $this->productLines;
    }

    public function addProductLine(string $name, int $quantity, int $price): void
    {
        $this->productLines[] = InvoiceProductLine::create($this, $name, $quantity, $price);
    }

    public function getTotalPrice(): int
    {
        return array_reduce(
            $this->productLines,
            fn (int $total, InvoiceProductLine $line) => $total + $line->getTotalPrice(),
            0
        );
    }

    /**
     * Domain Behavior: Transition to 'Sending' state.
     * Encapsulates all invariants required for this transition.
     * 
     * @throws InvalidInvoiceStateException If invoice is not in Draft status.
     * @throws InvoiceValidationException If product lines are empty or invalid.
     */
    public function markAsSending(): void
    {
        if ($this->status !== StatusEnum::Draft) {
            throw InvalidInvoiceStateException::mustBeDraft($this->status);
        }

        if (empty($this->productLines)) {
            throw InvoiceValidationException::emptyProductLines();
        }

        // Invariant: All lines must have positive quantity and price
        foreach ($this->productLines as $line) {
            if ($line->getQuantity() <= 0 || $line->getPrice() <= 0) {
                throw InvoiceValidationException::invalidProductLines();
            }
        }

        $this->status = StatusEnum::Sending;
    }

    /**
     * Domain Behavior: Transition to 'SentToClient' state.
     * This is an idempotent operation based on the delivery event.
     */
    public function markAsSentToClient(): void
    {
        if ($this->status === StatusEnum::Sending) {
            $this->status = StatusEnum::SentToClient;
        }
    }
}
