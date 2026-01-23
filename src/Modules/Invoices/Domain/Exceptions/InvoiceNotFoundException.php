<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Exceptions;

use DomainException;

/**
 * Thrown when an invoice cannot be found.
 */
final class InvoiceNotFoundException extends DomainException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('Invoice not found with id: %s', $id));
    }
}
