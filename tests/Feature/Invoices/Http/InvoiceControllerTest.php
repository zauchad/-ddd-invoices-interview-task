<?php

declare(strict_types=1);

namespace Tests\Feature\Invoices\Http;

use Doctrine\ORM\EntityManagerInterface;
use Modules\Invoices\Domain\Models\Invoice;
use Modules\Notifications\Api\NotificationFacadeInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class InvoiceControllerTest extends WebTestCase
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
        $this->assertEquals('draft', $response['data']['status']);
    }

    public function test_can_view_invoice(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        
        $invoice = Invoice::create('Jane Doe', 'jane@example.com');
        $invoice->addProductLine('Product 1', 2, 100);
        $em->persist($invoice);
        $em->flush();

        $client->request('GET', "/api/invoices/{$invoice->getId()}");

        $this->assertResponseIsSuccessful();
        
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Jane Doe', $response['data']['customer_name']);
        $this->assertEquals(200, $response['data']['total_price']);
    }

    public function test_can_send_invoice(): void
    {
        $client = static::createClient();
        $container = static::getContainer();

        $mockFacade = $this->createMock(NotificationFacadeInterface::class);
        $mockFacade->expects($this->once())->method('notify');
        $container->set(NotificationFacadeInterface::class, $mockFacade);

        $invoice = Invoice::create('Jane Doe', 'jane@example.com');
        $invoice->addProductLine('Product 1', 1, 100);
        
        $em = $container->get(EntityManagerInterface::class);
        $em->persist($invoice);
        $em->flush();

        $client->request('POST', "/api/invoices/{$invoice->getId()}/send");

        $this->assertResponseIsSuccessful();
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('sending', $response['data']['status']);
    }

    public function test_cannot_send_empty_invoice(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $invoice = Invoice::create('Jane Doe', 'jane@example.com');
        $em->persist($invoice);
        $em->flush();

        $client->request('POST', "/api/invoices/{$invoice->getId()}/send");

        $this->assertResponseStatusCodeSame(400);
    }
}
