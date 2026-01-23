<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Application\Services;

use Modules\Invoices\Api\Dtos\CreateInvoiceDto;
use Modules\Invoices\Api\Dtos\InvoiceProductLineDto;
use Modules\Invoices\Application\Services\InvoiceService;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\Exceptions\InvalidInvoiceStateException;
use Modules\Invoices\Domain\Exceptions\InvoiceNotFoundException;
use Modules\Invoices\Domain\Models\Invoice;
use Modules\Invoices\Domain\Repositories\InvoiceRepositoryInterface;
use Modules\Notifications\Api\NotificationFacadeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class InvoiceServiceTest extends TestCase
{
    private InvoiceRepositoryInterface&MockObject $invoiceRepository;
    private NotificationFacadeInterface&MockObject $notificationFacade;
    private InvoiceService $invoiceService;

    protected function setUp(): void
    {
        $this->invoiceRepository = $this->createMock(InvoiceRepositoryInterface::class);
        $this->notificationFacade = $this->createMock(NotificationFacadeInterface::class);
        $this->invoiceService = new InvoiceService(
            $this->invoiceRepository,
            $this->notificationFacade
        );
    }

    public function test_create_invoice_sets_status_to_draft(): void
    {
        $dto = new CreateInvoiceDto(
            customerName: 'John',
            customerEmail: 'john@example.com',
            productLines: []
        );

        $this->invoiceRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Invoice $invoice) {
                return $invoice->getStatus() === StatusEnum::Draft
                    && $invoice->getCustomerName() === 'John'
                    && $invoice->getCustomerEmail() === 'john@example.com';
            }));

        $invoice = $this->invoiceService->createInvoice($dto);

        $this->assertEquals(StatusEnum::Draft, $invoice->getStatus());
    }

    public function test_create_invoice_with_product_lines(): void
    {
        $dto = new CreateInvoiceDto(
            customerName: 'John',
            customerEmail: 'john@example.com',
            productLines: [
                new InvoiceProductLineDto('Product A', 2, 100),
                new InvoiceProductLineDto('Product B', 1, 50),
            ]
        );

        $this->invoiceRepository->expects($this->once())->method('save');

        $invoice = $this->invoiceService->createInvoice($dto);

        $this->assertCount(2, $invoice->getProductLines());
        $this->assertEquals(250, $invoice->getTotalPrice());
    }

    public function test_send_invoice_throws_if_not_found(): void
    {
        $id = 'non-existent-id';
        
        $this->invoiceRepository->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn(null);

        $this->expectException(InvoiceNotFoundException::class);
        $this->expectExceptionMessage("Invoice not found with id: {$id}");

        $this->invoiceService->sendInvoice($id);
    }

    public function test_send_invoice_transitions_to_sending_and_notifies(): void
    {
        $invoice = Invoice::create('John', 'john@example.com');
        $invoice->addProductLine('Product A', 1, 100);

        $this->invoiceRepository->expects($this->once())
            ->method('find')
            ->willReturn($invoice);

        $this->invoiceRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Invoice $i) {
                return $i->getStatus() === StatusEnum::Sending;
            }));

        $this->notificationFacade->expects($this->once())
            ->method('notify');

        $this->invoiceService->sendInvoice('some-id');
    }

    public function test_send_invoice_throws_if_not_draft(): void
    {
        $invoice = Invoice::create('John', 'john@example.com');
        $invoice->addProductLine('Product A', 1, 100);
        $invoice->markAsSending(); // Already sent

        $this->invoiceRepository->expects($this->once())
            ->method('find')
            ->willReturn($invoice);

        $this->expectException(InvalidInvoiceStateException::class);

        $this->invoiceService->sendInvoice('some-id');
    }
}
