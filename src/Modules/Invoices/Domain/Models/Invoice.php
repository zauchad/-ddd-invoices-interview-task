<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Models;

use Exception;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Invoices\Domain\Enums\StatusEnum;

/**
 * @property string $id
 * @property string $customer_name
 * @property string $customer_email
 * @property StatusEnum $status
 * @property \Illuminate\Support\Collection|InvoiceProductLine[] $productLines
 */
class Invoice extends Model
{
    use HasUuids;

    protected $table = 'invoices';

    protected $fillable = [
        'customer_name',
        'customer_email',
        'status',
    ];

    protected $casts = [
        'status' => StatusEnum::class,
    ];

    public function productLines(): HasMany
    {
        return $this->hasMany(InvoiceProductLine::class);
    }

    public function getTotalPrice(): int
    {
        return $this->productLines->sum(fn (InvoiceProductLine $line) => $line->getTotalUnitPrice());
    }

    /**
     * Domain Behavior: Transition to 'Sending' state.
     * Encapsulates all invariants required for this transition to ensure data integrity.
     * 
     * @throws Exception If invariants (Draft status, non-empty valid lines) are violated.
     */
    public function markAsSending(): void
    {
        if ($this->status !== StatusEnum::Draft) {
            throw new Exception("Invoice must be in draft status to be sent");
        }

        if ($this->productLines->isEmpty()) {
            throw new Exception("Invoice must have product lines to be sent");
        }

        // Invariant: All lines must have positive quantity and price
        $hasInvalidLines = $this->productLines->contains(
            fn (InvoiceProductLine $line) => $line->quantity <= 0 || $line->price <= 0
        );

        if ($hasInvalidLines) {
            throw new Exception("All product lines must have positive quantity and price");
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
