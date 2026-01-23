<?php

declare(strict_types=1);

namespace Tests\Unit\Notification\Facades;

use Modules\Notifications\Api\Dtos\NotifyData;
use Modules\Notifications\Application\Facades\NotificationFacade;
use Modules\Notifications\Infrastructure\Drivers\DriverInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class NotificationFacadeTest extends TestCase
{
    private DriverInterface&MockObject $driver;
    private NotificationFacade $facade;

    protected function setUp(): void
    {
        $this->driver = $this->createMock(DriverInterface::class);
        $this->facade = new NotificationFacade($this->driver);
    }

    public function test_notify_delegates_to_driver(): void
    {
        $resourceId = Uuid::uuid4();
        $data = new NotifyData(
            resourceId: $resourceId,
            toEmail: 'test@example.com',
            subject: 'Test Subject',
            message: 'Test Message'
        );

        $this->driver->expects($this->once())
            ->method('send')
            ->with(
                'test@example.com',
                'Test Subject',
                'Test Message',
                $resourceId->toString()
            );

        $this->facade->notify($data);
    }
}
