<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Exceptions;

use DomainException;

final class InvoiceNotFoundException extends DomainException
{
    public static function withId(string $id): self
    {
        return new self("Invoice not found with id: {$id}");
    }
}
