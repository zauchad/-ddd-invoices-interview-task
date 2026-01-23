<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Exceptions;

use DomainException;
use Modules\Invoices\Domain\Enums\StatusEnum;

final class InvalidInvoiceStateException extends DomainException
{
    public static function mustBeDraft(StatusEnum $currentStatus): self
    {
        return new self("Invoice must be in draft status to be sent, current status: {$currentStatus->value}");
    }
}
