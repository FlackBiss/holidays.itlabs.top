<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\WeatherView;
use App\Entity\SiteSettings;
use App\Service\WeatherService;
use Doctrine\ORM\EntityManagerInterface;

final readonly class WeatherProvider implements ProviderInterface
{
    public function __construct(private WeatherService $weather, private EntityManagerInterface $em) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): WeatherView
    {
        $settings=$this->em->getRepository(SiteSettings::class)->findOneBy(['code'=>'main']);
        return $this->weather->current($settings?->latitude ?? 56.0343584, $settings?->longitude ?? 37.6029333, $settings?->weatherCacheTtl ?? 900);
    }
}
