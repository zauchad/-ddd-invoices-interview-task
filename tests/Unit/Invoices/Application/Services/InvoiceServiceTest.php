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

final class InvoiceServiceTest extends TestCase
{
    private InvoiceRepositoryInterface&MockObject $invoiceRepository;
    private NotificationFacadeInterface&MockObject $notificationFacade;
    private InvoiceService $service;

    protected function setUp(): void
    {
        $this->invoiceRepository = $this->createMock(InvoiceRepositoryInterface::class);
        $this->notificationFacade = $this->createMock(NotificationFacadeInterface::class);
        $this->service = new InvoiceService($this->invoiceRepository, $this->notificationFacade);
    }

    public function test_create_invoice_sets_status_to_draft(): void
    {
        $dto = new CreateInvoiceDto('John', 'john@example.com', []);

        $this->invoiceRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(fn(Invoice $i) => 
                $i->getStatus() === StatusEnum::Draft && $i->getCustomerName() === 'John'
            ));

        $invoice = $this->service->createInvoice($dto);

        $this->assertEquals(StatusEnum::Draft, $invoice->getStatus());
    }

    public function test_create_invoice_with_product_lines(): void
    {
        $dto = new CreateInvoiceDto('John', 'john@example.com', [
            new InvoiceProductLineDto('Product A', 2, 100),
            new InvoiceProductLineDto('Product B', 1, 50),
        ]);

        $this->invoiceRepository->expects($this->once())->method('save');

        $invoice = $this->service->createInvoice($dto);

        $this->assertCount(2, $invoice->getProductLines());
        $this->assertEquals(250, $invoice->getTotalPrice());
    }

    public function test_send_invoice_throws_if_not_found(): void
    {
        $this->invoiceRepository->method('find')->willReturn(null);

        $this->expectException(InvoiceNotFoundException::class);
        $this->service->sendInvoice('non-existent-id');
    }

    public function test_send_invoice_transitions_to_sending_and_notifies(): void
    {
        $invoice = Invoice::create('John', 'john@example.com');
        $invoice->addProductLine('Product A', 1, 100);

        $this->invoiceRepository->method('find')->willReturn($invoice);
        $this->invoiceRepository->expects($this->once())->method('save');
        $this->notificationFacade->expects($this->once())->method('notify');

        $this->service->sendInvoice('some-id');

        $this->assertEquals(StatusEnum::Sending, $invoice->getStatus());
    }

    public function test_send_invoice_throws_if_not_draft(): void
    {
        $invoice = Invoice::create('John', 'john@example.com');
        $invoice->addProductLine('Product A', 1, 100);
        $invoice->markAsSending();

        $this->invoiceRepository->method('find')->willReturn($invoice);

        $this->expectException(InvalidInvoiceStateException::class);
        $this->service->sendInvoice('some-id');
    }
}
