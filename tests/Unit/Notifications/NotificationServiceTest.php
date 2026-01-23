<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use Modules\Notifications\Api\Events\ResourceDeliveredEvent;
use Modules\Notifications\Application\Services\NotificationService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class NotificationServiceTest extends TestCase
{
    #[Test]
    public function dispatches_delivered_event(): void
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $service = new NotificationService($dispatcher);
        $uuid = Uuid::uuid4();

        $dispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::callback(
                fn(ResourceDeliveredEvent $e) => $e->resourceId->equals($uuid)
            ));

        $service->delivered($uuid->toString());
    }
}
