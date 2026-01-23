<?php

declare(strict_types=1);

namespace Tests\Functional\Invoices;

use Doctrine\ORM\EntityManagerInterface;
use Modules\Invoices\Domain\Models\Invoice;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ViewInvoiceTest extends WebTestCase
{
    #[Test]
    public function returns_invoice_details(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $invoice = Invoice::create('John Doe', 'john@example.com');
        $invoice->addProductLine('Product A', 2, 100);
        $em->persist($invoice);
        $em->flush();

        $client->request('GET', "/api/invoices/{$invoice->getId()}");

        self::assertResponseIsSuccessful();
        
        $data = json_decode($client->getResponse()->getContent(), true)['data'];
        self::assertEquals('John Doe', $data['customer_name']);
        self::assertEquals(200, $data['total_price']);
        self::assertCount(1, $data['product_lines']);
    }

    #[Test]
    public function returns_404_for_non_existent_invoice(): void
    {
        $client = self::createClient();

        $client->request('GET', '/api/invoices/00000000-0000-0000-0000-000000000000');

        self::assertResponseStatusCodeSame(404);
    }
}
