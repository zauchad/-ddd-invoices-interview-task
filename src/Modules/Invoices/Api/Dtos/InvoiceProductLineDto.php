<?php

declare(strict_types=1);

namespace Modules\Invoices\Api\Dtos;

final readonly class InvoiceProductLineDto
{
    public function __construct(
        public string $name,
        public int $quantity,
        public int $price
    ) {}
}

