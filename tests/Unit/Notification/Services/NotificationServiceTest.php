<?php

declare(strict_types=1);

namespace Tests\Unit\Notification\Services;

use Modules\Notifications\Api\Events\ResourceDeliveredEvent;
use Modules\Notifications\Application\Services\NotificationService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class NotificationServiceTest extends TestCase
{
    private EventDispatcherInterface&MockObject $dispatcher;
    private NotificationService $service;

    protected function setUp(): void
    {
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->service = new NotificationService($this->dispatcher);
    }

    public function test_delivered_dispatches_event(): void
    {
        $uuid = Uuid::uuid4();

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (ResourceDeliveredEvent $event) use ($uuid) {
                return $event->resourceId->toString() === $uuid->toString();
            }));

        $this->service->delivered($uuid->toString());
    }
}
