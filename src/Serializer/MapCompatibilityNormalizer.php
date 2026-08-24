<?php

namespace App\Serializer;

use ApiPlatform\Metadata\Operation;
use App\Entity\KioskTerminal;
use App\Entity\MapPlace;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final readonly class MapCompatibilityNormalizer implements NormalizerInterface
{
    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')]
        private NormalizerInterface $normalizer,
    ) {}

    public function normalize(mixed $object, ?string $format = null, array $context = []): array
    {
        $data = $this->normalizer->normalize($object, $format, $context);
        $operation = $context['operation'] ?? null;
        $uri = $operation instanceof Operation ? (string) $operation->getUriTemplate() : '';
        if ($object instanceof MapPlace && str_starts_with($uri, '/objects')) {
            $data['node'] = $object->node?->getId();
            $data['area'] = $object->area?->getId();
            $data['floor'] = $object->plan?->getId();
            $data['title'] = $object->name;
        } elseif ($object instanceof KioskTerminal && str_starts_with($uri, '/terminals')) {
            $data['node'] = $object->startNode?->getId();
            $data['area'] = $object->area?->getId();
            $data['floor'] = $object->area?->plan?->getId() ?? $object->startNode?->plan?->getId();
            $data['title'] = $object->name;
        }
        return $data;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof MapPlace || $data instanceof KioskTerminal;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [MapPlace::class => true, KioskTerminal::class => true];
    }
}
