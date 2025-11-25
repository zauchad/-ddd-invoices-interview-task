<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Application\Services;

use Mockery;
use Modules\Invoices\Api\Dtos\CreateInvoiceDto;
use Modules\Invoices\Application\Services\InvoiceService;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\Models\Invoice;
use Modules\Invoices\Domain\Models\InvoiceProductLine;
use Modules\Invoices\Domain\Repositories\InvoiceRepositoryInterface;
use Modules\Notifications\Api\NotificationFacadeInterface;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class InvoiceServiceTest extends TestCase
{
    private $invoiceRepository;
    private $notificationFacade;
    private $invoiceService;

    protected function setUp(): void
    {
        $this->invoiceRepository = Mockery::mock(InvoiceRepositoryInterface::class);
        $this->notificationFacade = Mockery::mock(NotificationFacadeInterface::class);
        $this->invoiceService = new InvoiceService(
            $this->invoiceRepository,
            $this->notificationFacade
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_create_invoice_sets_status_to_draft(): void
    {
        $dto = new CreateInvoiceDto(
            customerName: 'John',
            customerEmail: 'john@example.com',
            productLines: []
        );

        // Mock the save for the invoice
        $this->invoiceRepository->shouldReceive('save')
            ->once()
            ->withArgs(function ($arg) {
                return $arg instanceof Invoice && $arg->status === StatusEnum::Draft;
            });

        $this->invoiceService->createInvoice($dto);
    }

    public function test_send_invoice_throws_if_not_draft(): void
    {
        $invoice = Mockery::mock(Invoice::class)->makePartial();
        $invoice->status = StatusEnum::SentToClient;
        $invoice->id = Uuid::uuid4()->toString();

        $this->invoiceRepository->shouldReceive('find')->andReturn($invoice);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Invoice must be in draft status to be sent");

        $this->invoiceService->sendInvoice($invoice->id);
    }
}
