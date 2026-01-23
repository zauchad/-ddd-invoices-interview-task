<?php

declare(strict_types=1);

namespace Modules\Invoices\Presentation\Http;

use DomainException;
use Modules\Invoices\Api\Dtos\CreateInvoiceDto;
use Modules\Invoices\Api\Dtos\InvoiceProductLineDto;
use Modules\Invoices\Application\Services\InvoiceService;
use Modules\Invoices\Domain\Exceptions\InvoiceNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class InvoiceController extends AbstractController
{
    public function __construct(
        private readonly InvoiceService $invoiceService
    ) {}

    public function view(string $id): JsonResponse
    {
        $invoice = $this->invoiceService->getInvoice($id);
        
        if (!$invoice) {
            throw new NotFoundHttpException('Invoice not found');
        }

        return $this->json([
            'data' => $this->transformInvoice($invoice)
        ]);
    }

    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        // Basic validation
        if (empty($data['customer_name']) || empty($data['customer_email'])) {
            throw new BadRequestHttpException('customer_name and customer_email are required');
        }

        $productLines = array_map(
            fn (array $line) => new InvoiceProductLineDto(
                name: $line['name'] ?? '',
                quantity: (int) ($line['quantity'] ?? 0),
                price: (int) ($line['price'] ?? 0)
            ),
            $data['product_lines'] ?? []
        );

        $invoice = $this->invoiceService->createInvoice(new CreateInvoiceDto(
            customerName: $data['customer_name'],
            customerEmail: $data['customer_email'],
            productLines: $productLines
        ));

        return $this->json(
            ['data' => $this->transformInvoice($invoice)],
            Response::HTTP_CREATED
        );
    }

    public function send(string $id): JsonResponse
    {
        try {
            $this->invoiceService->sendInvoice($id);
        } catch (InvoiceNotFoundException $e) {
            throw new NotFoundHttpException($e->getMessage());
        } catch (DomainException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        return $this->view($id);
    }

    private function transformInvoice(object $invoice): array
    {
        return [
            'id' => $invoice->getId()->toString(),
            'status' => $invoice->getStatus()->value,
            'customer_name' => $invoice->getCustomerName(),
            'customer_email' => $invoice->getCustomerEmail(),
            'product_lines' => array_map(fn ($line) => [
                'name' => $line->getName(),
                'quantity' => $line->getQuantity(),
                'unit_price' => $line->getPrice(),
                'total_unit_price' => $line->getTotalPrice(),
            ], $invoice->getProductLines()),
            'total_price' => $invoice->getTotalPrice(),
        ];
    }
}
