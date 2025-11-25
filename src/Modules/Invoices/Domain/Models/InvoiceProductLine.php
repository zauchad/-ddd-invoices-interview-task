<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $invoice_id
 * @property string $name
 * @property int $price
 * @property int $quantity
 */
class InvoiceProductLine extends Model
{
    use HasUuids;

    protected $table = 'invoice_product_lines';

    protected $fillable = [
        'name',
        'price',
        'quantity',
    ];

    protected $casts = [
        'price' => 'integer',
        'quantity' => 'integer',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Calculate total for this line item.
     */
    public function getTotalUnitPrice(): int
    {
        return $this->quantity * $this->price;
    }
}
