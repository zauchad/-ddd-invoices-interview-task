<?php

declare(strict_types=1);

namespace Modules\Notifications\Presentation\Http;

use Modules\Notifications\Application\Services\NotificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class NotificationController extends AbstractController
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    #[Route('/api/notification/hook/{action}/{reference}', methods: ['GET'], requirements: [
        'action' => '[a-zA-Z]+',
        'reference' => '[0-9a-f\-]{36}'
    ])]
    public function hook(string $action, string $reference): JsonResponse
    {
        match ($action) {
            'delivered' => $this->notificationService->delivered($reference),
            default => null,
        };

        return new JsonResponse(status: Response::HTTP_OK);
    }
}
