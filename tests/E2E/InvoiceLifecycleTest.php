<?php

declare(strict_types=1);

namespace Tests\E2E;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class InvoiceLifecycleTest extends WebTestCase
{
    #[Test]
    public function complete_invoice_lifecycle(): void
    {
        $client = self::createClient();

        // 1. Create invoice
        $client->request('POST', '/api/invoices', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'customer_name' => 'E2E Customer',
            'customer_email' => 'e2e@example.com',
            'product_lines' => [
                ['name' => 'Service A', 'quantity' => 5, 'price' => 200],
                ['name' => 'Service B', 'quantity' => 2, 'price' => 300],
            ],
        ]));

        self::assertResponseStatusCodeSame(201);
        $createData = json_decode($client->getResponse()->getContent(), true)['data'];
        $invoiceId = $createData['id'];

        self::assertEquals('draft', $createData['status']);
        self::assertEquals(1600, $createData['total_price']);

        // 2. View invoice
        $client->request('GET', "/api/invoices/{$invoiceId}");

        self::assertResponseIsSuccessful();
        $viewData = json_decode($client->getResponse()->getContent(), true)['data'];
        
        self::assertEquals('draft', $viewData['status']);
        self::assertCount(2, $viewData['product_lines']);

        // 3. Send invoice
        $client->request('POST', "/api/invoices/{$invoiceId}/send");

        self::assertResponseIsSuccessful();
        $sendData = json_decode($client->getResponse()->getContent(), true)['data'];
        
        self::assertEquals('sending', $sendData['status']);

        // 4. Delivery webhook
        $client->request('GET', "/api/notification/hook/delivered/{$invoiceId}");

        self::assertResponseIsSuccessful();

        // 5. Verify final status
        $client->request('GET', "/api/invoices/{$invoiceId}");

        self::assertResponseIsSuccessful();
        $finalData = json_decode($client->getResponse()->getContent(), true)['data'];
        
        self::assertEquals('sent-to-client', $finalData['status']);
    }

    #[Test]
    public function cannot_send_invoice_twice(): void
    {
        $client = self::createClient();

        // Create and send invoice
        $client->request('POST', '/api/invoices', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'customer_name' => 'Test',
            'customer_email' => 'test@example.com',
            'product_lines' => [
                ['name' => 'Item', 'quantity' => 1, 'price' => 100],
            ],
        ]));

        $invoiceId = json_decode($client->getResponse()->getContent(), true)['data']['id'];

        $client->request('POST', "/api/invoices/{$invoiceId}/send");
        self::assertResponseIsSuccessful();

        // Try to send again
        $client->request('POST', "/api/invoices/{$invoiceId}/send");
        self::assertResponseStatusCodeSame(400);
    }

    #[Test]
    public function webhook_only_affects_sending_invoices(): void
    {
        $client = self::createClient();

        // Create draft invoice
        $client->request('POST', '/api/invoices', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'customer_name' => 'Test',
            'customer_email' => 'test@example.com',
            'product_lines' => [
                ['name' => 'Item', 'quantity' => 1, 'price' => 100],
            ],
        ]));

        $invoiceId = json_decode($client->getResponse()->getContent(), true)['data']['id'];

        // Webhook on draft (should not change status)
        $client->request('GET', "/api/notification/hook/delivered/{$invoiceId}");
        self::assertResponseIsSuccessful();

        $client->request('GET', "/api/invoices/{$invoiceId}");
        $data = json_decode($client->getResponse()->getContent(), true)['data'];
        
        self::assertEquals('draft', $data['status']);
    }
}
