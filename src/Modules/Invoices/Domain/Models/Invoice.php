<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Models;

use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\Exceptions\InvalidInvoiceStateException;
use Modules\Invoices\Domain\Exceptions\InvoiceValidationException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final class Invoice
{
    /** @var array<InvoiceProductLine> */
    private array $productLines = [];

    private function __construct(
        private readonly UuidInterface $id,
        private readonly string $customerName,
        private readonly string $customerEmail,
        private StatusEnum $status
    ) {}

    public static function create(string $customerName, string $customerEmail): self
    {
        return new self(Uuid::uuid4(), $customerName, $customerEmail, StatusEnum::Draft);
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

    /** @return array<InvoiceProductLine> */
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
            fn(int $total, InvoiceProductLine $line) => $total + $line->getTotalPrice(),
            0
        );
    }

    public function markAsSending(): void
    {
        if ($this->status !== StatusEnum::Draft) {
            throw InvalidInvoiceStateException::mustBeDraft($this->status);
        }

        if (empty($this->productLines)) {
            throw InvoiceValidationException::emptyProductLines();
        }

        foreach ($this->productLines as $line) {
            if ($line->getQuantity() <= 0 || $line->getPrice() <= 0) {
                throw InvoiceValidationException::invalidProductLines();
            }
        }

        $this->status = StatusEnum::Sending;
    }

    public function markAsSentToClient(): void
    {
        if ($this->status === StatusEnum::Sending) {
            $this->status = StatusEnum::SentToClient;
        }
    }
}
