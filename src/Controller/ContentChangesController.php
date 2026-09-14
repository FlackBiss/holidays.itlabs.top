<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final readonly class ContentChangesController
{
    public function __construct(private Connection $connection) {}

    #[Route('/api/changes', name: 'api_content_changes', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        $since = $request->query->all()['since'] ?? null;
        $headers = ['Cache-Control' => 'no-store, private'];
        if ($since !== null && (!is_string($since) || preg_match('/^[a-f0-9]{32}$/D', $since) !== 1)) {
            return new JsonResponse(['error' => 'since должен содержать version из предыдущего ответа.'], 400, $headers);
        }

        $revision = $this->connection->fetchAssociative('SELECT version, updated_at FROM content_revision WHERE id = 1');
        if ($revision === false) {
            throw new \LogicException('Content revision is missing. Run database migrations.');
        }

        return new JsonResponse([
            'version' => $revision['version'],
            'changed' => $since !== $revision['version'],
            'updatedAt' => (new \DateTimeImmutable($revision['updated_at'], new \DateTimeZone('UTC')))->format(DATE_ATOM),
        ], 200, $headers);
    }
}
