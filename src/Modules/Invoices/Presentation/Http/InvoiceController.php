<?php

declare(strict_types=1);

namespace Modules\Invoices\Presentation\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Invoices\Application\Services\InvoiceService;
use Modules\Invoices\Presentation\Http\Resources\InvoiceResource;

class InvoiceController extends Controller
{
    public function __construct(
        private InvoiceService $invoiceService
    ) {}

    public function view(string $id): InvoiceResource
    {
        $invoice = $this->invoiceService->getInvoice($id);

        if (!$invoice) {
            abort(404);
        }

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

        $invoice = $this->invoiceService->createInvoice($validated);

        return new InvoiceResource($invoice);
    }

    public function send(string $id): InvoiceResource
    {
        try {
            $this->invoiceService->sendInvoice($id);
        } catch (\Exception $e) {
            abort(400, $e->getMessage());
        }

        $invoice = $this->invoiceService->getInvoice($id);
        return new InvoiceResource($invoice);
    }
}
