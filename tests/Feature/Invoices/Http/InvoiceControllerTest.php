<?php

declare(strict_types=1);

namespace Tests\Feature\Invoices\Http;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\Models\Invoice;
use Modules\Notifications\Api\NotificationFacadeInterface;
use Tests\TestCase;

class InvoiceControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_invoice_draft(): void
    {
        $data = [
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'product_lines' => [],
        ];

        $response = $this->postJson(route('invoices.create'), $data);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'customer_name' => 'John Doe',
                    'customer_email' => 'john@example.com',
                    'status' => 'draft',
                    'product_lines' => [],
                    'total_price' => 0,
                ]
            ]);

        $this->assertDatabaseHas('invoices', [
            'customer_email' => 'john@example.com',
            'status' => 'draft',
        ]);
    }

    public function test_can_view_invoice(): void
    {
        $invoice = Invoice::create([
            'customer_name' => 'Jane Doe',
            'customer_email' => 'jane@example.com',
            'status' => StatusEnum::Draft,
        ]);

        $invoice->productLines()->create([
            'name' => 'Product 1',
            'quantity' => 2,
            'price' => 100,
        ]);

        $response = $this->getJson(route('invoices.view', $invoice->id));

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $invoice->id,
                    'customer_name' => 'Jane Doe',
                    'total_price' => 200,
                    'product_lines' => [
                        [
                            'name' => 'Product 1',
                            'quantity' => 2,
                            'unit_price' => 100,
                            'total_unit_price' => 200,
                        ]
                    ]
                ]
            ]);
    }

    public function test_can_send_invoice(): void
    {
        $this->mock(NotificationFacadeInterface::class)
            ->shouldReceive('notify')
            ->once();

        $invoice = Invoice::create([
            'customer_name' => 'Jane Doe',
            'customer_email' => 'jane@example.com',
            'status' => StatusEnum::Draft,
        ]);

        $invoice->productLines()->create([
            'name' => 'Product 1',
            'quantity' => 1,
            'price' => 100,
        ]);

        $response = $this->postJson(route('invoices.send', $invoice->id));

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'sending');

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'sending',
        ]);
    }

    public function test_cannot_send_empty_invoice(): void
    {
        $invoice = Invoice::create([
            'customer_name' => 'Jane Doe',
            'customer_email' => 'jane@example.com',
            'status' => StatusEnum::Draft,
        ]);

        $response = $this->postJson(route('invoices.send', $invoice->id));

        $response->assertStatus(400);
    }

    public function test_cannot_send_invoice_with_invalid_lines(): void
    {
        $invoice = Invoice::create([
            'customer_name' => 'Jane Doe',
            'customer_email' => 'jane@example.com',
            'status' => StatusEnum::Draft,
        ]);

        $invoice->productLines()->create([
            'name' => 'Product 1',
            'quantity' => 0,
            'price' => 100,
        ]);

        $response = $this->postJson(route('invoices.send', $invoice->id));

        $response->assertStatus(400);
    }
}

