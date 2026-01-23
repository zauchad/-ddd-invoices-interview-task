<?php

declare(strict_types=1);

namespace Tests\Functional\Invoices;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CreateInvoiceTest extends WebTestCase
{
    #[Test]
    public function creates_draft_invoice(): void
    {
        $client = self::createClient();

        $client->request('POST', '/api/invoices', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'product_lines' => [],
        ]));

        self::assertResponseStatusCodeSame(201);
        
        $data = json_decode($client->getResponse()->getContent(), true)['data'];
        self::assertEquals('John Doe', $data['customer_name']);
        self::assertEquals('draft', $data['status']);
        self::assertEmpty($data['product_lines']);
    }

    #[Test]
    public function creates_invoice_with_product_lines(): void
    {
        $client = self::createClient();

        $client->request('POST', '/api/invoices', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'customer_name' => 'Jane Doe',
            'customer_email' => 'jane@example.com',
            'product_lines' => [
                ['name' => 'Service A', 'quantity' => 2, 'price' => 100],
                ['name' => 'Service B', 'quantity' => 1, 'price' => 50],
            ],
        ]));

        self::assertResponseStatusCodeSame(201);
        
        $data = json_decode($client->getResponse()->getContent(), true)['data'];
        self::assertCount(2, $data['product_lines']);
        self::assertEquals(250, $data['total_price']);
    }

    #[Test]
    public function rejects_missing_customer_name(): void
    {
        $client = self::createClient();

        $client->request('POST', '/api/invoices', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'customer_email' => 'john@example.com',
        ]));

        self::assertResponseStatusCodeSame(400);
    }
}
