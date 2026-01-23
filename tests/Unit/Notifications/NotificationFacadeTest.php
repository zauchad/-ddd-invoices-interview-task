<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use Modules\Notifications\Api\Dtos\NotifyData;
use Modules\Notifications\Application\Facades\NotificationFacade;
use Modules\Notifications\Infrastructure\Drivers\DriverInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

final class NotificationFacadeTest extends TestCase
{
    #[Test]
    public function delegates_to_driver(): void
    {
        $driver = $this->createMock(DriverInterface::class);
        $facade = new NotificationFacade($driver);
        $resourceId = Uuid::uuid4();

        $driver->expects(self::once())
            ->method('send')
            ->with('test@example.com', 'Subject', 'Message', $resourceId->toString());

        $facade->notify(new NotifyData($resourceId, 'test@example.com', 'Subject', 'Message'));
    }
}
