<?php

declare(strict_types=1);

namespace Modules\Invoices\Api\Dtos;

final readonly class CreateInvoiceDto
{
    /** @param InvoiceProductLineDto[] $productLines */
    public function __construct(
        public string $customerName,
        public string $customerEmail,
        public array $productLines
    ) {}
}
