<?php

namespace App\Controller;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final readonly class OpenApiController
{
    public function __construct(
        private OpenApiFactoryInterface $factory,
        private NormalizerInterface $normalizer,
    ) {
    }

    #[Route('/api/docs.jsonopenapi', name: 'api_openapi_current', methods: ['GET'], priority: 100)]
    public function __invoke(Request $request): JsonResponse
    {
        $version = (string) $request->query->get('spec_version', '3.0.0');
        $context = ['spec_version' => $version, 'base_url' => $request->getBaseUrl()];
        $openApi = ($this->factory)($context);

        return new JsonResponse($this->normalizer->normalize($openApi, 'json', $context));
    }
}
