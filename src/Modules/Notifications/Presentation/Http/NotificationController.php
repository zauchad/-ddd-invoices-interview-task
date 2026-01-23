<?php

declare(strict_types=1);

namespace Modules\Notifications\Presentation\Http;

use Modules\Notifications\Application\Services\NotificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class NotificationController extends AbstractController
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    public function hook(string $action, string $reference): JsonResponse
    {
        match ($action) {
            'delivered' => $this->notificationService->delivered(reference: $reference),
            default => null,
        };

        return new JsonResponse(data: null, status: Response::HTTP_OK);
    }
}
