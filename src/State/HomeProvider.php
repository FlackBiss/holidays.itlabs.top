<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\HomeView;
use App\Entity\SiteSettings;
use App\Entity\StandbyMedia;
use App\Service\WeatherService;
use Doctrine\ORM\EntityManagerInterface;

/** @implements ProviderInterface<HomeView> */
final readonly class HomeProvider implements ProviderInterface
{
    public function __construct(private EntityManagerInterface $em, private WeatherService $weather)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): HomeView
    {
        $settings = $this->em->getRepository(SiteSettings::class)->findOneBy(['code' => 'main']);
        $weather = $this->weather->current(
            $settings?->latitude ?? 56.0343584,
            $settings?->longitude ?? 37.6029333,
            $settings?->weatherCacheTtl ?? 900,
        );

        $view = new HomeView();
        $now = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Moscow'));
        $view->serverDateTime = [
            'iso' => $now->format(DATE_ATOM),
            'date' => $now->format('Y-m-d'),
            'time' => $now->format('H:i:s'),
            'timezone' => $now->getTimezone()->getName(),
            'timestamp' => $now->getTimestamp(),
        ];
        $view->settings = $settings ? [
            'id' => $settings->getId(),
            'companyName' => $settings->companyName,
            'logo' => $settings->getFileUrl(),
            'idleTimeoutSeconds' => $settings->idleTimeoutSeconds,
            'slideDurationSeconds' => $settings->slideDurationSeconds,
        ] : null;
        $view->weather = get_object_vars($weather);
        $view->standby = array_map(
            static fn (StandbyMedia $item): array => [
                'id' => $item->getId(),
                'title' => $item->title,
                'type' => $item->type->value,
                'url' => $item->getUrl(),
                'priority' => $item->priority,
            ],
            $this->em->getRepository(StandbyMedia::class)->findBy(
                ['active' => true],
                ['priority' => 'ASC', 'id' => 'ASC'],
            ),
        );

        return $view;
    }
}
