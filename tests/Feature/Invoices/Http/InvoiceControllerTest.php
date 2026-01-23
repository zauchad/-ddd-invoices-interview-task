<?php

declare(strict_types=1);

namespace Tests\Feature\Invoices\Http;

use Doctrine\ORM\EntityManagerInterface;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\Models\Invoice;
use Modules\Notifications\Api\NotificationFacadeInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class InvoiceControllerTest extends WebTestCase
{
    public function test_can_create_invoice_draft(): void
    {
        $client = static::createClient();
        
        $client->request('POST', '/api/invoices', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'product_lines' => [],
        ]));

        $this->assertResponseStatusCodeSame(201);
        
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('John Doe', $response['data']['customer_name']);
        $this->assertEquals('john@example.com', $response['data']['customer_email']);
        $this->assertEquals('draft', $response['data']['status']);
    }

    public function test_can_view_invoice(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        
        // Create invoice via service
        $invoice = Invoice::create('Jane Doe', 'jane@example.com');
        $invoice->addProductLine('Product 1', 2, 100);
        
        $em = $container->get(EntityManagerInterface::class);
        $em->persist($invoice);
        $em->flush();

        $client->request('GET', '/api/invoices/' . $invoice->getId()->toString());

        $this->assertResponseIsSuccessful();
        
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Jane Doe', $response['data']['customer_name']);
        $this->assertEquals(200, $response['data']['total_price']);
    }

    public function test_can_send_invoice(): void
    {
        $client = static::createClient();
        $container = static::getContainer();

        // Mock notification facade
        $mockFacade = $this->createMock(NotificationFacadeInterface::class);
        $mockFacade->expects($this->once())->method('notify');
        $container->set(NotificationFacadeInterface::class, $mockFacade);

        // Create invoice
        $invoice = Invoice::create('Jane Doe', 'jane@example.com');
        $invoice->addProductLine('Product 1', 1, 100);
        
        $em = $container->get(EntityManagerInterface::class);
        $em->persist($invoice);
        $em->flush();

        $client->request('POST', '/api/invoices/' . $invoice->getId()->toString() . '/send');

        $this->assertResponseIsSuccessful();
        
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('sending', $response['data']['status']);
    }

    public function test_cannot_send_empty_invoice(): void
    {
        $client = static::createClient();
        $container = static::getContainer();

        $invoice = Invoice::create('Jane Doe', 'jane@example.com');
        // No product lines
        
        $em = $container->get(EntityManagerInterface::class);
        $em->persist($invoice);
        $em->flush();

        $client->request('POST', '/api/invoices/' . $invoice->getId()->toString() . '/send');

        $this->assertResponseStatusCodeSame(400);
    }
}
