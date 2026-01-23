<?php

declare(strict_types=1);

namespace Modules\Notifications\Application\Services;

use Modules\Notifications\Api\Events\ResourceDeliveredEvent;
use Ramsey\Uuid\Uuid;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final readonly class NotificationService
{
    public function __construct(
        private EventDispatcherInterface $dispatcher
    ) {}

    public function delivered(string $reference): void
    {
        $this->dispatcher->dispatch(new ResourceDeliveredEvent(Uuid::fromString($reference)));
    }
}
