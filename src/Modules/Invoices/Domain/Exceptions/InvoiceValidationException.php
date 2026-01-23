<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Exceptions;

use DomainException;

/**
 * Thrown when invoice data fails domain validation rules.
 */
final class InvoiceValidationException extends DomainException
{
    public static function emptyProductLines(): self
    {
        return new self('Invoice must have product lines to be sent');
    }

    public static function invalidProductLines(): self
    {
        return new self('All product lines must have positive quantity and price');
    }
}
