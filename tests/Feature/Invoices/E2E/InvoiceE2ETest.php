<?php

declare(strict_types=1);

namespace Tests\Feature\Invoices\E2E;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\Models\Invoice;
use Modules\Notifications\Api\Events\ResourceDeliveredEvent;
use Tests\TestCase;

class InvoiceE2ETest extends TestCase
{
    use RefreshDatabase;

    /**
     * End-to-End test: Complete invoice lifecycle
     */
    public function test_complete_invoice_lifecycle(): void
    {
        // Step 1: Create Invoice
        $createResponse = $this->postJson(route('invoices.create'), [
            'customer_name' => 'E2E Test Customer',
            'customer_email' => 'e2e@example.com',
            'product_lines' => [
                [
                    'name' => 'Service A',
                    'quantity' => 5,
                    'price' => 200,
                ],
                [
                    'name' => 'Service B',
                    'quantity' => 2,
                    'price' => 300,
                ],
            ],
        ]);

        $createResponse->assertStatus(201);
        $invoiceId = $createResponse->json('data.id');
        $this->assertEquals('draft', $createResponse->json('data.status'));
        $this->assertEquals(1600, $createResponse->json('data.total_price'));

        // Step 2: View Invoice
        $viewResponse = $this->getJson(route('invoices.view', $invoiceId));
        $viewResponse->assertStatus(200)
            ->assertJsonPath('data.id', $invoiceId)
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonCount(2, 'data.product_lines');

        // Step 3: Send Invoice
        $sendResponse = $this->postJson(route('invoices.send', $invoiceId));
        $sendResponse->assertStatus(200)
            ->assertJsonPath('data.status', 'sending');

        // Verify database state
        $this->assertDatabaseHas('invoices', [
            'id' => $invoiceId,
            'status' => 'sending',
        ]);

        // Step 4: Simulate Delivery Webhook
        // Note: The route is registered in API routes, so it needs /api prefix
        $webhookResponse = $this->getJson("/api/notification/hook/delivered/{$invoiceId}");
        $webhookResponse->assertStatus(200);

        // Step 5: Verify Final Status
        $finalResponse = $this->getJson(route('invoices.view', $invoiceId));
        $finalResponse->assertStatus(200)
            ->assertJsonPath('data.status', 'sent-to-client');

        $this->assertDatabaseHas('invoices', [
            'id' => $invoiceId,
            'status' => 'sent-to-client',
        ]);
    }

    public function test_cannot_send_already_sent_invoice(): void
    {
        $createResponse = $this->postJson(route('invoices.create'), [
            'customer_name' => 'Test Customer',
            'customer_email' => 'test@example.com',
            'product_lines' => [
                ['name' => 'Product', 'quantity' => 1, 'price' => 100],
            ],
        ]);

        $invoiceId = $createResponse->json('data.id');

        $this->postJson(route('invoices.send', $invoiceId))->assertStatus(200);

        $secondSendResponse = $this->postJson(route('invoices.send', $invoiceId));
        $secondSendResponse->assertStatus(400);
    }

    public function test_webhook_only_updates_sending_status(): void
    {
        $createResponse = $this->postJson(route('invoices.create'), [
            'customer_name' => 'Test Customer',
            'customer_email' => 'test@example.com',
            'product_lines' => [
                ['name' => 'Product', 'quantity' => 1, 'price' => 100],
            ],
        ]);

        $invoiceId = $createResponse->json('data.id');

        // Try webhook on draft invoice (should not change status)
        $this->getJson("/api/notification/hook/delivered/{$invoiceId}")->assertStatus(200);

        $viewResponse = $this->getJson(route('invoices.view', $invoiceId));
        $viewResponse->assertJsonPath('data.status', 'draft');

        // Now send it
        $this->postJson(route('invoices.send', $invoiceId))->assertStatus(200);

        // Webhook should now work
        $this->getJson("/api/notification/hook/delivered/{$invoiceId}")->assertStatus(200);

        $finalResponse = $this->getJson(route('invoices.view', $invoiceId));
        $finalResponse->assertJsonPath('data.status', 'sent-to-client');
    }
}
