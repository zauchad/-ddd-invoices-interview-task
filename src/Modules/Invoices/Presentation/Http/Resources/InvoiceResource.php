<?php

declare(strict_types=1);

namespace Modules\Invoices\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_id' => $this->id, // Requirement asks for "Invoice ID", using UUID
            'status' => $this->status->value,
            'customer_name' => $this->customer_name,
            'customer_email' => $this->customer_email,
            'product_lines' => $this->productLines->map(function ($line) {
                return [
                    'name' => $line->name,
                    'quantity' => $line->quantity,
                    'unit_price' => $line->price,
                    'total_unit_price' => $line->getTotalUnitPrice(),
                ];
            }),
            'total_price' => $this->getTotalPrice(),
        ];
    }
}

