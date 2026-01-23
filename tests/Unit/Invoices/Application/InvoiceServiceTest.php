<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Application;

use Modules\Invoices\Api\Dtos\CreateInvoiceDto;
use Modules\Invoices\Api\Dtos\InvoiceProductLineDto;
use Modules\Invoices\Application\Services\InvoiceService;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\Exceptions\InvalidInvoiceStateException;
use Modules\Invoices\Domain\Exceptions\InvoiceNotFoundException;
use Modules\Invoices\Domain\Models\Invoice;
use Modules\Invoices\Domain\Repositories\InvoiceRepositoryInterface;
use Modules\Notifications\Api\NotificationFacadeInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class InvoiceServiceTest extends TestCase
{
    private InvoiceRepositoryInterface&MockObject $repository;
    private NotificationFacadeInterface&MockObject $notificationFacade;
    private InvoiceService $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(InvoiceRepositoryInterface::class);
        $this->notificationFacade = $this->createMock(NotificationFacadeInterface::class);
        $this->service = new InvoiceService($this->repository, $this->notificationFacade);
    }

    #[Test]
    public function creates_invoice_in_draft(): void
    {
        $dto = new CreateInvoiceDto('John', 'john@example.com', []);

        $this->repository->expects(self::once())->method('save');

        $invoice = $this->service->createInvoice($dto);

        self::assertEquals(StatusEnum::Draft, $invoice->getStatus());
        self::assertEquals('John', $invoice->getCustomerName());
    }

    #[Test]
    public function creates_invoice_with_product_lines(): void
    {
        $dto = new CreateInvoiceDto('John', 'john@example.com', [
            new InvoiceProductLineDto('Product A', 2, 100),
            new InvoiceProductLineDto('Product B', 1, 50),
        ]);

        $this->repository->expects(self::once())->method('save');

        $invoice = $this->service->createInvoice($dto);

        self::assertCount(2, $invoice->getProductLines());
        self::assertEquals(250, $invoice->getTotalPrice());
    }

    #[Test]
    public function throws_when_invoice_not_found(): void
    {
        $this->repository->method('find')->willReturn(null);

        $this->expectException(InvoiceNotFoundException::class);
        $this->service->sendInvoice('non-existent');
    }

    #[Test]
    public function sends_invoice_and_notifies(): void
    {
        $invoice = Invoice::create('John', 'john@example.com');
        $invoice->addProductLine('Product A', 1, 100);

        $this->repository->method('find')->willReturn($invoice);
        $this->repository->expects(self::once())->method('save');
        $this->notificationFacade->expects(self::once())->method('notify');

        $this->service->sendInvoice('id');

        self::assertEquals(StatusEnum::Sending, $invoice->getStatus());
    }

    #[Test]
    public function throws_when_sending_non_draft(): void
    {
        $invoice = Invoice::create('John', 'john@example.com');
        $invoice->addProductLine('Product A', 1, 100);
        $invoice->markAsSending();

        $this->repository->method('find')->willReturn($invoice);

        $this->expectException(InvalidInvoiceStateException::class);
        $this->service->sendInvoice('id');
    }
}
