<?php

declare(strict_types=1);

namespace Tests\Unit\Notification\Facades;

use Modules\Notifications\Api\Dtos\NotifyData;
use Modules\Notifications\Application\Facades\NotificationFacade;
use Modules\Notifications\Infrastructure\Drivers\DriverInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

final class NotificationFacadeTest extends TestCase
{
    public function test_notify_delegates_to_driver(): void
    {
        $driver = $this->createMock(DriverInterface::class);
        $facade = new NotificationFacade($driver);
        
        $resourceId = Uuid::uuid4();
        $data = new NotifyData($resourceId, 'test@example.com', 'Test Subject', 'Test Message');

        $driver->expects($this->once())
            ->method('send')
            ->with('test@example.com', 'Test Subject', 'Test Message', $resourceId->toString());

        $facade->notify($data);
    }
}
