<?php

namespace App\State;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\SectionView;
use App\Entity\ContentItem;
use App\Entity\ContentPage;
use App\Entity\AnimationPoster;
use App\Entity\GalleryMedia;
use App\Entity\NewsPoster;
use App\Entity\PublicTransportRoute;
use App\Entity\SectionDocument;
use App\Entity\TaganrogSliderImage;
use App\Enum\ContentPageType;
use App\Enum\ContentSection;
use App\Enum\SectionSlug;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** @implements ProviderInterface<SectionView> */
final readonly class SectionProvider implements ProviderInterface
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): SectionView|array
    {
        if ($operation instanceof GetCollection) {
            return array_map(
                static fn (SectionSlug $slug) => self::view($slug, []),
                SectionSlug::cases(),
            );
        }

        $slug = SectionSlug::tryFrom((string) ($uriVariables['slug'] ?? ''));
        if (!$slug) {
            throw new NotFoundHttpException('Раздел не найден.');
        }

        return self::view($slug, $this->sectionData($slug));
    }

    /** @param array<string, mixed> $data */
    private static function view(SectionSlug $slug, array $data): SectionView
    {
        $view = new SectionView();
        $view->slug = $slug->value;
        $view->title = $slug->label();
        $view->data = $data;

        return $view;
    }

    /** @return array<string, mixed> */
    private function sectionData(SectionSlug $slug): array
    {
        $data = match ($slug) {
            SectionSlug::ABOUT => ['page' => $this->page(ContentPageType::ABOUT)],
            SectionSlug::GUEST_INFO => [
                'page' => $this->page(ContentPageType::GUEST_INFO),
                'links' => $this->documents(SectionSlug::GUEST_INFO),
            ],
            SectionSlug::SERVICE_HOURS => [
                'page' => $this->page(ContentPageType::SERVICE_HOURS),
            ],
            SectionSlug::MEAL_TIMES => ['page' => $this->mealTimesPage()],
            SectionSlug::CONNECT => ['page' => $this->connectPage()],
            SectionSlug::TRANSFER => ['page' => $this->transferPage()],
            SectionSlug::PUBLIC_TRANSPORT => ['routes' => $this->publicTransportRoutes()],
            SectionSlug::NEWS => ['posters' => $this->orderedMedia(NewsPoster::class)],
            SectionSlug::ANIMATION => ['posters' => $this->orderedMedia(AnimationPoster::class)],
            SectionSlug::INFRASTRUCTURE => ['page' => $this->infrastructurePage()],
            SectionSlug::MEDICAL_CENTER => [
                'page' => $this->page(ContentPageType::MEDICAL_CENTER),
                'departments' => $this->hierarchy(ContentSection::MEDICAL_DEPARTMENT, ContentSection::MEDICAL_SERVICE),
            ],
            SectionSlug::GALLERY => ['media' => $this->orderedMedia(GalleryMedia::class)],
            SectionSlug::PRICES => ['categories' => $this->documents(SectionSlug::PRICES)],
            SectionSlug::RESIDENCE_RULES => ['page' => $this->residenceRulesPage()],
            SectionSlug::UAV_ALERT => ['memos' => $this->documents(SectionSlug::UAV_ALERT)],
            SectionSlug::TAGANROG => ['page' => $this->taganrogPage()],
        };

        // Every screen may additionally be represented by one file or by a
        // category -> files hierarchy. This keeps future static screens out of
        // schema migrations while structured schedules/routes remain intact.
        $data['documents'] = $this->documents($slug);

        return $data;
    }

    /** @return list<array<string, mixed>> */
    private function documents(SectionSlug $section): array
    {
        $documents = $this->em->getRepository(SectionDocument::class)->findBy(
            ['section' => $section, 'active' => true],
            ['priority' => 'ASC', 'id' => 'ASC'],
        );
        $roots = array_filter($documents, static fn (SectionDocument $document) => $document->parent === null);

        return array_values(array_map(
            fn (SectionDocument $document): array => $this->documentTree($document, $documents),
            $roots,
        ));
    }

    /** @param list<SectionDocument> $documents @return array<string, mixed> */
    private function documentTree(SectionDocument $document, array $documents): array
    {
        $data = $this->document($document);
        $children = array_filter(
            $documents,
            static fn (SectionDocument $child) => $child->parent?->getId() === $document->getId(),
        );
        $data['items'] = array_values(array_map(
            fn (SectionDocument $child): array => $this->documentTree($child, $documents),
            $children,
        ));

        return $data;
    }

    /** @return array<string, mixed> */
    private function document(SectionDocument $document): array
    {
        $data = [
            'id' => $document->getId(),
            'parentId' => $document->getParentId(),
            'title' => $document->title,
            'url' => $document->getUrl(),
            'priority' => $document->priority,
        ];
        if (!in_array($document->section, [SectionSlug::GUEST_INFO, SectionSlug::PUBLIC_TRANSPORT, SectionSlug::PRICES], true)) {
            $data['description'] = $document->description;
        }

        return $data;
    }

    /** @return array<string, mixed>|null */
    private function page(ContentPageType $type): ?array
    {
        $page = $this->em->getRepository(ContentPage::class)->findOneBy(['type' => $type, 'active' => true]);
        if (!$page instanceof ContentPage) {
            return null;
        }

        return [
            'id' => $page->getId(),
            'title' => $page->title,
            'description' => $page->description,
            'imageUrl' => $page->getImageUrl(),
            'logoUrl' => $page->getLogoUrl(),
            'mascotOneUrl' => $page->getLogoUrl(),
            'mascotTwoUrl' => $page->getMascotTwoUrl(),
            'documentUrl' => $page->getDocumentUrl(),
            'serviceQrLinks' => array_map(static fn ($link): array => [
                'id' => $link->getId(),
                'title' => $link->title,
                'description' => $link->description,
                'url' => $link->getUrl(),
                'priority' => $link->priority,
            ], $page->serviceQrLinks->toArray()),
            'data' => $page->data,
        ];
    }

    /** @return array<string, mixed>|null */
    private function mealTimesPage(): ?array
    {
        $page = $this->em->getRepository(ContentPage::class)->findOneBy(['type' => ContentPageType::MEAL_TIMES, 'active' => true]);
        if (!$page instanceof ContentPage) return null;

        return [
            'id' => $page->getId(),
            'hedgehogUrl' => $page->getImageUrl(),
            'mascotUrl' => $page->getImageUrl(),
            'diningHalls' => [
                'imageUrl' => $page->getLogoUrl(),
                'mainTerritory' => ['description' => $page->getMainTerritoryDiningHallDescription()],
                'buildingSevenTerritory' => ['description' => $page->getBuildingSevenDiningHallDescription()],
            ],
            'cafe' => ['description' => $page->getCafeDescription(), 'imageUrl' => $page->getMascotTwoUrl()],
            'phytoBar' => ['description' => $page->getPhytoBarDescription(), 'imageUrl' => $page->getExtraImageUrl()],
        ];
    }

    /** @return array<string, mixed>|null */
    private function connectPage(): ?array
    {
        $page = $this->em->getRepository(ContentPage::class)->findOneBy(['type' => ContentPageType::CONNECT, 'active' => true]);
        if (!$page instanceof ContentPage) return null;

        return [
            'id' => $page->getId(),
            'title' => $page->title,
            'imageUrl' => $page->getImageUrl(),
            'logoUrl' => $page->getLogoUrl(),
            'prizeBenefits' => $page->getPrizeBenefits(),
            'importantNotices' => $page->getImportantNotices(),
            'rewards' => $page->getConnectRewards(),
        ];
    }

    /** @return array<string, mixed>|null */
    private function infrastructurePage(): ?array
    {
        $page = $this->em->getRepository(ContentPage::class)->findOneBy(['type' => ContentPageType::INFRASTRUCTURE, 'active' => true]);
        if (!$page instanceof ContentPage) return null;

        return [
            'id' => $page->getId(),
            'mainTerritory' => $page->getMainTerritoryInfrastructure(),
            'buildingSevenTerritory' => $page->getBuildingSevenInfrastructure(),
        ];
    }

    /** @return array<string, mixed>|null */
    private function residenceRulesPage(): ?array
    {
        $page = $this->em->getRepository(ContentPage::class)->findOneBy(['type' => ContentPageType::RESIDENCE_RULES, 'active' => true]);
        if (!$page instanceof ContentPage) return null;

        return [
            'id' => $page->getId(),
            'title' => $page->title,
            'fullRulesQrUrl' => $page->getImageUrl(),
            'checkInTime' => $page->getCheckInTime(),
            'checkOutTime' => $page->getCheckOutTime(),
            'placementRules' => $page->getPlacementRules(),
            'visitorPassText' => $page->getVisitorPassText(),
            'safetyRules' => $page->getSafetyRules(),
            'medicalProcedureRules' => $page->getMedicalProcedureRules(),
            'placementIconUrl' => $page->getLogoUrl(),
            'visitorPassIconUrl' => $page->getMascotTwoUrl(),
            'medicalProceduresIconUrl' => $page->getExtraImageUrl(),
            'damageCompensationQrUrl' => $page->getFifthImageUrl(),
        ];
    }

    /** @return array<string, mixed>|null */
    private function taganrogPage(): ?array
    {
        $page = $this->em->getRepository(ContentPage::class)->findOneBy(['type' => ContentPageType::TAGANROG, 'active' => true]);
        if (!$page instanceof ContentPage) return null;

        return [
            'id' => $page->getId(),
            'title' => $page->title,
            'siteQrUrl' => $page->getImageUrl(),
            'logoUrl' => $page->getLogoUrl(),
            'images' => array_values(array_filter([$page->getMascotTwoUrl(), $page->getExtraImageUrl(), $page->getFifthImageUrl()])),
            'slider' => array_map(static fn (TaganrogSliderImage $image): array => [
                'id' => $image->getId(),
                'url' => $image->getUrl(),
                'priority' => $image->priority,
            ], $page->sliderImages->toArray()),
            'mission' => $page->getMission(),
            'description' => $page->description,
            'aboutSanatorium' => $page->getAboutSanatorium(),
        ];
    }

    /** @return array<string, mixed>|null */
    private function transferPage(): ?array
    {
        $page = $this->em->getRepository(ContentPage::class)->findOneBy(['type' => ContentPageType::TRANSFER, 'active' => true]);
        if (!$page instanceof ContentPage) return null;

        return [
            'id' => $page->getId(),
            'mapUrl' => $page->getImageUrl(),
            'mainTerritoryDepartureTimes' => $page->getMainTerritoryDepartureTimes(),
            'buildingSevenDepartureTimes' => $page->getBuildingSevenDepartureTimes(),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function publicTransportRoutes(): array
    {
        $routes = $this->em->getRepository(PublicTransportRoute::class)->findBy(['active' => true], ['priority' => 'ASC', 'id' => 'ASC']);

        return array_map(static fn (PublicTransportRoute $route): array => [
            'id' => $route->getId(),
            'routeNumber' => $route->routeNumber,
            'routeMapUrl' => $route->getRouteMapUrl(),
            'schedules' => $route->getSchedules(),
            'priority' => $route->priority,
        ], $routes);
    }

    /** @return list<array<string, mixed>> */
    private function items(ContentSection $section): array
    {
        $items = $this->em->getRepository(ContentItem::class)->findBy(
            ['section' => $section, 'active' => true],
            ['priority' => 'ASC', 'id' => 'ASC'],
        );

        return array_map($this->item(...), $items);
    }

    /** @return list<array<string, mixed>> */
    private function hierarchy(ContentSection $parentSection, ?ContentSection $childSection = null): array
    {
        $parents = $this->em->getRepository(ContentItem::class)->findBy(
            ['section' => $parentSection, 'parent' => null, 'active' => true],
            ['priority' => 'ASC', 'id' => 'ASC'],
        );
        $children = $this->em->getRepository(ContentItem::class)->findBy(
            ['section' => $childSection ?? $parentSection, 'active' => true],
            ['priority' => 'ASC', 'id' => 'ASC'],
        );

        return array_map(function (ContentItem $parent) use ($children): array {
            $data = $this->item($parent);
            $data['items'] = array_values(array_map(
                $this->item(...),
                array_filter($children, static fn (ContentItem $child) => $child->parent?->getId() === $parent->getId()),
            ));

            return $data;
        }, $parents);
    }

    /** @return array<string, mixed> */
    private function item(ContentItem $item): array
    {
        return [
            'id' => $item->getId(),
            'parentId' => $item->getParentId(),
            'title' => $item->title,
            'description' => $item->description,
            'territory' => $item->territory,
            'phone' => $item->phone,
            'workingDays' => $item->workingDays,
            'startsAt' => $item->startsAt,
            'endsAt' => $item->endsAt,
            'breakStartsAt' => $item->breakStartsAt,
            'breakEndsAt' => $item->breakEndsAt,
            'roundTheClock' => $item->roundTheClock,
            'times' => $item->times,
            'weekdaysTimes' => $item->weekdaysTimes,
            'weekendsTimes' => $item->weekendsTimes,
            'url' => $item->url,
            'qrCodeUrl' => $item->url ? '/api/content-items/'.$item->getId().'/qr' : null,
            'onlineBooking' => $item->onlineBooking,
            'points' => $item->points,
            'priority' => $item->priority,
            'fileUrl' => $item->getFileUrl(),
            'fileType' => $item->fileType?->value,
            'data' => $item->data,
        ];
    }

    /** @param class-string $class @return list<array<string, mixed>> */
    private function orderedMedia(string $class): array
    {
        return array_map(static fn (object $media): array => [
            'id' => $media->getId(), 'title' => $media->title,
            'type' => $media->type->value, 'url' => $media->getUrl(),
            'priority' => $media->priority,
        ], $this->em->getRepository($class)->findBy(['active' => true], ['priority' => 'ASC', 'id' => 'ASC']));
    }
}
