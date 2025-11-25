<?php

declare(strict_types=1);

namespace Modules\Invoices\Presentation\Http;

use Exception;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\Invoices\Api\Dtos\CreateInvoiceDto;
use Modules\Invoices\Api\Dtos\InvoiceProductLineDto;
use Modules\Invoices\Application\Services\InvoiceService;
use Modules\Invoices\Presentation\Http\Resources\InvoiceResource;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceService $invoiceService
    ) {}

    public function view(string $id): InvoiceResource
    {
        // concise 404 check
        abort_unless($invoice = $this->invoiceService->getInvoice($id), 404);

        return new InvoiceResource($invoice);
    }

    public function create(Request $request): InvoiceResource
    {
        $validated = $request->validate([
            'customer_name' => 'required|string',
            'customer_email' => 'required|email',
            'product_lines' => 'array',
            'product_lines.*.name' => 'required|string',
            'product_lines.*.quantity' => 'required|integer',
            'product_lines.*.price' => 'required|integer',
        ]);

        // Map request array to DTOs inline
        $invoice = $this->invoiceService->createInvoice(new CreateInvoiceDto(
            customerName: $validated['customer_name'],
            customerEmail: $validated['customer_email'],
            productLines: array_map(
                fn (array $line) => new InvoiceProductLineDto(
                    name: $line['name'],
                    quantity: (int)$line['quantity'],
                    price: (int)$line['price']
                ),
                $validated['product_lines'] ?? []
            )
        ));

        return new InvoiceResource($invoice);
    }

    public function send(string $id): InvoiceResource
    {
        try {
            $this->invoiceService->sendInvoice($id);
        } catch (Exception $e) {
            abort(400, $e->getMessage());
        }

        // Retrieve fresh instance after update
        return $this->view($id);
    }
}
