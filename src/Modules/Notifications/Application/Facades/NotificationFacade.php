<?php

declare(strict_types=1);

namespace Modules\Notifications\Application\Facades;

use Modules\Notifications\Api\Dtos\NotifyData;
use Modules\Notifications\Api\NotificationFacadeInterface;
use Modules\Notifications\Infrastructure\Drivers\DriverInterface;

/**
 * Facade for the Notifications module.
 * 
 * This is the public API that other modules use to interact with Notifications.
 * It hides internal implementation details.
 */
final readonly class NotificationFacade implements NotificationFacadeInterface
{
    public function __construct(
        private DriverInterface $driver
    ) {}

    public function notify(NotifyData $data): void
    {
        $this->driver->send(
            toEmail: $data->toEmail,
            subject: $data->subject,
            message: $data->message,
            reference: $data->resourceId->toString()
        );
    }
}
