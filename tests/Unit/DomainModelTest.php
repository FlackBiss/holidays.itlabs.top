<?php

namespace App\Tests\Unit;

use App\ApiResource\NavigationRequest;
use App\Entity\MapPlace;
use App\Entity\MapPlacePhoto;
use App\Entity\PublicTransportRoute;
use App\Entity\StandbyMedia;
use App\Entity\ContentPage;
use App\Entity\RoomCategory;
use App\Entity\RoomCategoryPhoto;
use App\Enum\MediaType;
use App\Enum\PlaceType;
use App\Enum\SectionSlug;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Validation;

final class DomainModelTest extends TestCase
{
    public function testMediaTypeIsDetectedFromMimeType(): void
    {
        $path=tempnam(sys_get_temp_dir(),'holiday_video_'); file_put_contents($path,"video");
        try { $asset=new StandbyMedia(); $asset->setFile(new File($path, false)); self::assertSame(MediaType::IMAGE,$asset->type); }
        finally { @unlink($path); }
    }

    public function testInfrastructureCannotContainResidentialFields(): void
    {
        $place=new MapPlace(); $place->type=PlaceType::INFRASTRUCTURE; $place->buildingNumber='7'; $place->floorCount=3;
        $violations=Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator()->validate($place);
        self::assertGreaterThan(0,$violations->count());
    }

    public function testNavigationRequiresDestination(): void
    {
        $request=new NavigationRequest();
        $violations=Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator()->validate($request);
        self::assertGreaterThan(0,$violations->count());
    }

    public function testServiceDescriptionsAreStoredInStructuredData(): void
    {
        $page = new ContentPage();
        $page->setReceptionDescription('Круглосуточно');
        $page->setRegistryDescription('Ежедневно с 08:00 до 19:00');
        $page->setNurseDescription('Телефон 59-26');

        self::assertSame('Круглосуточно', $page->getReceptionDescription());
        self::assertSame('Ежедневно с 08:00 до 19:00', $page->getRegistryDescription());
        self::assertSame('Телефон 59-26', $page->getNurseDescription());
    }

    public function testConnectRewardPointsMustBeBetweenOneAndFive(): void
    {
        $page = new ContentPage();
        $page->setConnectRewards([
            ['achievement' => 'Участие в активности', 'points' => 0],
            ['achievement' => 'Главное достижение', 'points' => 6],
        ]);

        $violations = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator()->validate($page);

        self::assertCount(2, $violations);
    }

    public function testTransportTimesUseTwentyFourHourFormat(): void
    {
        $route = new PublicTransportRoute();
        $route->routeNumber = '271';
        $route->setSchedules([['stopName' => 'Аксаково', 'days' => 'Будни', 'times' => ['25:00']]]);

        $transfer = new ContentPage();
        $transfer->type = \App\Enum\ContentPageType::TRANSFER;
        $transfer->setMainTerritoryDepartureTimes(['09:00', 'не время']);

        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
        self::assertCount(1, $validator->validate($route));
        self::assertCount(1, $validator->validate($transfer));
    }

    public function testEveryFrontendSectionHasAStableSlugAndLabel(): void
    {
        self::assertCount(16, SectionSlug::cases());
        self::assertSame('Анимационная программа', SectionSlug::ANIMATION->label());
        foreach (SectionSlug::cases() as $section) {
            self::assertNotSame('', $section->value);
            self::assertNotSame('', $section->label());
        }
    }

    public function testRoomCategoriesAreSharedCatalogEntriesWithOrderedPhotos(): void
    {
        $firstBuilding = new MapPlace();
        $secondBuilding = new MapPlace();
        $firstCategory = new RoomCategory();
        $firstPhoto = new RoomCategoryPhoto();
        $firstPhoto->priority = 2;
        $firstPhoto->setFileName('first-room.webp');

        $firstCategory->title = 'Семейный';
        $firstCategory->addPhoto($firstPhoto);
        $firstBuilding->addRoomCategory($firstCategory);
        $secondBuilding->addRoomCategory($firstCategory);

        self::assertTrue($firstCategory->places->contains($firstBuilding));
        self::assertTrue($firstCategory->places->contains($secondBuilding));
        self::assertTrue($firstBuilding->roomCategories->contains($firstCategory));
        self::assertTrue($secondBuilding->roomCategories->contains($firstCategory));
        self::assertSame($firstCategory, $firstPhoto->category);
        self::assertSame('/uploads/room-categories/first-room.webp', $firstPhoto->getUrl());
    }

    public function testMapObjectPhotoHasDirectVichUrl(): void
    {
        $photo = new MapPlacePhoto();
        $photo->setFileName('building.webp');

        self::assertSame('/uploads/map-places/building.webp', $photo->getUrl());
    }

    public function testMapObjectIconIsUploadedDirectlyAndKeepsApiShape(): void
    {
        $place = new MapPlace();
        $place->setIconFileName('building-icon.svg');

        self::assertSame('/uploads/map-place-icons/building-icon.svg', $place->getIconUrl());
        self::assertSame(['url' => '/uploads/map-place-icons/building-icon.svg'], $place->getIcon());
    }
}
