<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Models;

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

    /**
     * Calculate the total price of the invoice.
     * This is a domain logic helper, keeping business rules near the data.
     */
    public function getTotalPrice(): int
    {
        return $this->productLines->sum(fn (InvoiceProductLine $line) => $line->getTotalUnitPrice());
    }
}
