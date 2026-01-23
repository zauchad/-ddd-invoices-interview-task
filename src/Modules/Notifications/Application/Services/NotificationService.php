<?php

declare(strict_types=1);

namespace Modules\Notifications\Application\Services;

use Modules\Notifications\Api\Events\ResourceDeliveredEvent;
use Ramsey\Uuid\Uuid;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class NotificationService
{
    public function __construct(
        private readonly EventDispatcherInterface $dispatcher
    ) {}

    public function delivered(string $reference): void
    {
        $this->dispatcher->dispatch(new ResourceDeliveredEvent(
            resourceId: Uuid::fromString($reference)
        ));
    }
}
