<?php

declare(strict_types=1);

namespace Tests\Functional\Invoices;

use Doctrine\ORM\EntityManagerInterface;
use Modules\Invoices\Domain\Models\Invoice;
use Modules\Notifications\Api\NotificationFacadeInterface;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SendInvoiceTest extends WebTestCase
{
    #[Test]
    public function sends_valid_invoice(): void
    {
        $client = self::createClient();
        $container = self::getContainer();

        $mockFacade = $this->createMock(NotificationFacadeInterface::class);
        $mockFacade->expects(self::once())->method('notify');
        $container->set(NotificationFacadeInterface::class, $mockFacade);

        $invoice = Invoice::create('John Doe', 'john@example.com');
        $invoice->addProductLine('Product', 1, 100);

        $em = $container->get(EntityManagerInterface::class);
        $em->persist($invoice);
        $em->flush();

        $client->request('POST', "/api/invoices/{$invoice->getId()}/send");

        self::assertResponseIsSuccessful();
        
        $data = json_decode($client->getResponse()->getContent(), true)['data'];
        self::assertEquals('sending', $data['status']);
    }

    #[Test]
    public function rejects_empty_invoice(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $invoice = Invoice::create('John Doe', 'john@example.com');
        $em->persist($invoice);
        $em->flush();

        $client->request('POST', "/api/invoices/{$invoice->getId()}/send");

        self::assertResponseStatusCodeSame(400);
    }

    #[Test]
    public function rejects_already_sent_invoice(): void
    {
        $client = self::createClient();
        $container = self::getContainer();

        $mockFacade = $this->createMock(NotificationFacadeInterface::class);
        $container->set(NotificationFacadeInterface::class, $mockFacade);

        $invoice = Invoice::create('John Doe', 'john@example.com');
        $invoice->addProductLine('Product', 1, 100);
        $invoice->markAsSending();

        $em = $container->get(EntityManagerInterface::class);
        $em->persist($invoice);
        $em->flush();

        $client->request('POST', "/api/invoices/{$invoice->getId()}/send");

        self::assertResponseStatusCodeSame(400);
    }
}
