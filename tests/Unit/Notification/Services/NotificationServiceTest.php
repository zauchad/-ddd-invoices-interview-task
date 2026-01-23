<?php

declare(strict_types=1);

namespace Tests\Unit\Notification\Services;

use Modules\Notifications\Api\Events\ResourceDeliveredEvent;
use Modules\Notifications\Application\Services\NotificationService;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class NotificationServiceTest extends TestCase
{
    public function test_delivered_dispatches_event(): void
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $service = new NotificationService($dispatcher);
        $uuid = Uuid::uuid4();

        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(fn(ResourceDeliveredEvent $e) => 
                $e->resourceId->toString() === $uuid->toString()
            ));

        $service->delivered($uuid->toString());
    }
}
