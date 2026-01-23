<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Exceptions;

use DomainException;
use Modules\Invoices\Domain\Enums\StatusEnum;

/**
 * Thrown when an operation is attempted on an invoice in an invalid state.
 */
final class InvalidInvoiceStateException extends DomainException
{
    public static function mustBeDraft(StatusEnum $currentStatus): self
    {
        return new self(
            sprintf('Invoice must be in draft status to be sent, current status: %s', $currentStatus->value)
        );
    }

    public static function cannotTransitionTo(StatusEnum $from, StatusEnum $to): self
    {
        return new self(
            sprintf('Cannot transition invoice from %s to %s', $from->value, $to->value)
        );
    }
}
