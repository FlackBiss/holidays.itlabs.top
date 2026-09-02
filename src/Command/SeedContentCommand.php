<?php

namespace App\Command;

use App\Entity\AnimationPoster;
use App\Entity\ContentItem;
use App\Entity\ContentPage;
use App\Entity\GalleryMedia;
use App\Entity\KioskTerminal;
use App\Entity\MapArea;
use App\Entity\MapEdge;
use App\Entity\MapNode;
use App\Entity\MapPlace;
use App\Entity\MapPlacePhoto;
use App\Entity\MapPlan;
use App\Entity\NewsPoster;
use App\Entity\PublicTransportRoute;
use App\Entity\RoomCategory;
use App\Entity\RoomCategoryPhoto;
use App\Entity\SectionDocument;
use App\Entity\ServiceQrLink;
use App\Entity\SiteSettings;
use App\Entity\StandbyMedia;
use App\Entity\TaganrogSliderImage;
use App\Enum\ContentPageType;
use App\Enum\ContentSection;
use App\Enum\PlaceType;
use App\Enum\SectionSlug;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[AsCommand(name: 'app:seed-content', description: 'Заполняет отсутствующие разделы и тестовую маршрутную сеть стартовыми данными')]
final class SeedContentCommand extends Command
{
    private bool $refresh = false;
    private int $created = 0;
    private int $updated = 0;

    public function __construct(
        private readonly EntityManagerInterface $em,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('refresh', null, InputOption::VALUE_NONE, 'Обновить ранее созданные стартовые записи');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->refresh = (bool) $input->getOption('refresh');

        $settings = $this->em->getRepository(SiteSettings::class)->findOneBy(['code' => 'main']) ?? new SiteSettings();
        if (!$settings->getId()) {
            $this->em->persist($settings);
            ++$this->created;
        }
        $settings->companyName = 'Каникулы в Аксаково';
        $this->singleFile($settings, 'logo-placeholder.svg');
        $settings->latitude = 56.0343584;
        $settings->longitude = 37.6029333;
        $settings->idleTimeoutSeconds = 120;
        $settings->modalTimeoutSeconds = 60;
        $settings->slideDurationSeconds = 10;
        $settings->maxGeoSnapDistanceMeters = 500;

        $this->seedPages();
        $this->seedContent();
        $this->seedPublicTransport();
        $this->seedOrderedMedia();
        // The second pass also repairs legacy rows that existed before direct
        // Vich fields were introduced and therefore have an empty file_name.
        $this->em->flush();
        $this->seedPages();
        $this->seedPublicTransport();
        $this->seedOrderedMedia();
        $this->seedSectionDocuments();
        $this->seedMap();

        $this->em->flush();
        $output->writeln(sprintf('<info>Стартовые данные готовы: создано %d, обновлено %d.</info>', $this->created, $this->updated));
        $output->writeln('<comment>SVG-файлы помечены как стартовые и должны быть заменены утверждёнными материалами через VichUploader в админке.</comment>');

        return Command::SUCCESS;
    }

    private function seedPages(): void
    {
        $about = $this->page(ContentPageType::ABOUT, [
            'title' => 'Каникулы в Аксаково',
            'description' => 'Санаторий для здорового отдыха, восстановления сил и санаторно-курортного лечения в Подмосковье.',
            'data' => [
                'advantages' => [
                    'Спокойный отдых недалеко от Москвы',
                    'Современные медицинские и оздоровительные технологии',
                    'Развитая инфраструктура двух территорий',
                    'Программы отдыха для взрослых и детей',
                ],
                'sourceUrl' => 'https://kanikuly-v-aksakovo.ru/',
            ],
        ]);
        $this->pageFiles($about, 'poster-placeholder.svg', 'logo-placeholder.svg');
        $medicalCenter = $this->page(ContentPageType::MEDICAL_CENTER, [
            'title' => 'Медицинский центр',
            'description' => null,
            'data' => [],
        ]);
        $this->pageFiles($medicalCenter, 'poster-placeholder.svg', 'logo-placeholder.svg');
        $this->pageMascotTwoFile($medicalCenter, 'logo-placeholder.svg');
        $mealTimes = $this->page(ContentPageType::MEAL_TIMES, [
            'title' => 'Время питания',
            'description' => null,
            'data' => [
                'mainTerritoryDiningHallDescription' => 'Завтрак, обед и ужин в обеденном зале основной территории — по актуальному расписанию санатория.',
                'buildingSevenDiningHallDescription' => 'Завтрак, обед и ужин в обеденном зале территории корпуса 7 — по отдельному расписанию.',
                'cafeDescription' => 'Кафе расположено в здании бассейна на втором этаже.',
                'phytoBarDescription' => 'Фитобар расположен в здании бассейна на втором этаже.',
            ],
        ]);
        if ($mealTimes->getMainTerritoryDiningHallDescription() === null) $mealTimes->setMainTerritoryDiningHallDescription('Завтрак, обед и ужин в обеденном зале основной территории — по актуальному расписанию санатория.');
        if ($mealTimes->getBuildingSevenDiningHallDescription() === null) $mealTimes->setBuildingSevenDiningHallDescription('Завтрак, обед и ужин в обеденном зале территории корпуса 7 — по отдельному расписанию.');
        $this->pageFiles($mealTimes, 'logo-placeholder.svg', 'poster-placeholder.svg');
        $this->pageMascotTwoFile($mealTimes, 'poster-placeholder.svg');
        $this->pageExtraImageFile($mealTimes, 'poster-placeholder.svg');
        $infrastructure = $this->page(ContentPageType::INFRASTRUCTURE, [
            'title' => 'Инфраструктура',
            'description' => null,
            'data' => [
                'mainTerritoryInfrastructure' => ['Сауна', 'Фито-бар', 'Косметология'],
                'buildingSevenInfrastructure' => ['Сауна', 'Фито-бар', 'Косметология'],
            ],
        ]);
        if ($infrastructure->getMainTerritoryInfrastructure() === []) $infrastructure->setMainTerritoryInfrastructure(['Сауна', 'Фито-бар', 'Косметология']);
        if ($infrastructure->getBuildingSevenInfrastructure() === []) $infrastructure->setBuildingSevenInfrastructure(['Сауна', 'Фито-бар', 'Косметология']);
        $transfer = $this->page(ContentPageType::TRANSFER, [
            'title' => 'Трансфер между территориями',
            'description' => null,
            'data' => [
                'mainTerritoryDepartureTimes' => ['09:00', '12:00', '15:00', '18:00'],
                'buildingSevenDepartureTimes' => ['09:30', '12:30', '15:30', '18:30'],
            ],
        ]);
        if ($transfer->getMainTerritoryDepartureTimes() === []) $transfer->setMainTerritoryDepartureTimes(['09:00', '12:00', '15:00', '18:00']);
        if ($transfer->getBuildingSevenDepartureTimes() === []) $transfer->setBuildingSevenDepartureTimes(['09:30', '12:30', '15:30', '18:30']);
        $this->pageImageFile($transfer, 'map-development.svg');
        $this->page(ContentPageType::GUEST_INFO, [
            'title' => 'Информация для гостей',
            'description' => 'Здесь собраны документы и сведения, необходимые для комфортного пребывания в санатории. За дополнительной информацией можно обратиться к сотрудникам ресепшн.',
            'data' => ['sourceUrl' => 'https://kanikuly-v-aksakovo.ru/home/poleznaya-informatsiya.html'],
        ]);
        $serviceHours = $this->page(ContentPageType::SERVICE_HOURS, [
            'title' => 'Контакты',
            'description' => null,
            'data' => [
                'receptionDescription' => 'Ежедневно, круглосуточно. Телефон основной территории: 59-00. Телефон корпуса 7 уточните на ресепшн.',
                'registryDescription' => 'Ежедневно с 08:00 до 19:00. Телефон: 59-29.',
                'nurseDescription' => 'Ежедневно, круглосуточно. Телефон: 59-26.',
            ],
        ]);
        $this->pageDocument($serviceHours, 'document-placeholder.pdf');
        foreach ([
            ['Оставить отзыв', 'QR-код для страницы отзывов'],
            ['Канал в MAX', 'QR-код канала санатория в MAX'],
            ['Изменения в работе служб', 'Актуальная информация об изменениях графика'],
        ] as $priority => [$title, $description]) {
            $qr = $this->em->getRepository(ServiceQrLink::class)->findOneBy(['page' => $serviceHours, 'title' => $title]) ?? new ServiceQrLink();
            if (!$qr->getId()) { $qr->title = $title; $serviceHours->addServiceQrLink($qr); $this->em->persist($qr); ++$this->created; }
            if (!$qr->getId() || $this->refresh) { $qr->description = $description; $qr->priority = $priority + 1; }
            $this->singleFile($qr, 'memo-placeholder.svg');
        }
        $connect = $this->page(ContentPageType::CONNECT, [
            'title' => 'Программа «Подключайся»',
            'description' => null,
            'data' => [
                'prizeBenefits' => ['Участвуйте в активностях программы', 'Копите баллы', 'Обменивайте баллы на призы'],
                'importantNotices' => ['Баллы начисляются только за подтверждённые достижения', 'Количество призов ограничено'],
                'connectRewards' => [
                    ['achievement' => 'Участие в активности', 'points' => 1],
                    ['achievement' => 'Выполнение задания программы', 'points' => 3],
                    ['achievement' => 'Главное достижение дня', 'points' => 5],
                ],
            ],
        ]);
        if ($connect->getPrizeBenefits() === []) $connect->setPrizeBenefits(['Участвуйте в активностях программы', 'Копите баллы', 'Обменивайте баллы на призы']);
        if ($connect->getImportantNotices() === []) $connect->setImportantNotices(['Баллы начисляются только за подтверждённые достижения', 'Количество призов ограничено']);
        if ($connect->getConnectRewards() === []) $connect->setConnectRewards([
            ['achievement' => 'Участие в активности', 'points' => 1],
            ['achievement' => 'Выполнение задания программы', 'points' => 3],
            ['achievement' => 'Главное достижение дня', 'points' => 5],
        ]);
        $this->pageFiles($connect, 'poster-placeholder.svg', 'logo-placeholder.svg');
        $rules = $this->page(ContentPageType::RESIDENCE_RULES, [
            'title' => 'Правила проживания',
            'description' => null,
            'data' => [
                'checkInTime' => '12:00',
                'checkOutTime' => '10:00',
                'placementRules' => ['Предъявите документ, удостоверяющий личность', 'Соблюдайте установленное время заезда и выезда'],
                'visitorPassText' => 'Посетители проходят на территорию санатория по пропуску.',
                'safetyRules' => ['Соблюдайте тишину и правила пожарной безопасности', 'Бережно относитесь к имуществу санатория'],
                'medicalProcedureRules' => ['Посещайте процедуры в назначенное время', 'Сообщайте врачу об изменениях самочувствия'],
            ],
        ]);
        if ($rules->getCheckInTime() === null) $rules->setCheckInTime('12:00');
        if ($rules->getCheckOutTime() === null) $rules->setCheckOutTime('10:00');
        if ($rules->getPlacementRules() === []) $rules->setPlacementRules(['Предъявите документ, удостоверяющий личность', 'Соблюдайте установленное время заезда и выезда']);
        if ($rules->getVisitorPassText() === null) $rules->setVisitorPassText('Посетители проходят на территорию санатория по пропуску.');
        if ($rules->getSafetyRules() === []) $rules->setSafetyRules(['Соблюдайте тишину и правила пожарной безопасности', 'Бережно относитесь к имуществу санатория']);
        if ($rules->getMedicalProcedureRules() === []) $rules->setMedicalProcedureRules(['Посещайте процедуры в назначенное время', 'Сообщайте врачу об изменениях самочувствия']);
        $this->pageFiles($rules, 'logo-placeholder.svg', 'memo-placeholder.svg');
        $this->pageMascotTwoFile($rules, 'memo-placeholder.svg');
        $this->pageExtraImageFile($rules, 'memo-placeholder.svg');
        $this->pageFifthImageFile($rules, 'logo-placeholder.svg');
        $taganrog = $this->page(ContentPageType::TAGANROG, [
            'title' => 'Каникулы в Таганроге',
            'description' => 'Санаторий в Таганроге объединяет отдых у моря, оздоровление и насыщенную культурную программу.',
            'data' => [
                'mission' => 'Создавать пространство для качественного отдыха, восстановления здоровья и ярких впечатлений.',
                'aboutSanatorium' => ['Комфортное размещение', 'Оздоровительные программы', 'Отдых рядом с морем'],
            ],
        ]);
        if ($taganrog->description === null) $taganrog->description = 'Санаторий в Таганроге объединяет отдых у моря, оздоровление и насыщенную культурную программу.';
        if ($taganrog->getMission() === null) $taganrog->setMission('Создавать пространство для качественного отдыха, восстановления здоровья и ярких впечатлений.');
        if ($taganrog->getAboutSanatorium() === []) $taganrog->setAboutSanatorium(['Комфортное размещение', 'Оздоровительные программы', 'Отдых рядом с морем']);
        $this->pageFiles($taganrog, 'logo-placeholder.svg', 'logo-placeholder.svg');
        $this->pageMascotTwoFile($taganrog, 'poster-placeholder.svg');
        $this->pageExtraImageFile($taganrog, 'poster-placeholder.svg');
        $this->pageFifthImageFile($taganrog, 'poster-placeholder.svg');
        if ($taganrog->sliderImages->isEmpty()) {
            $sliderImage = new TaganrogSliderImage();
            $sliderImage->priority = 1;
            $taganrog->addSliderImage($sliderImage);
            $this->prepareTaganrogSliderImageFile($sliderImage, 'poster-placeholder.svg');
            $this->em->persist($sliderImage);
            ++$this->created;
        }
    }

    private function seedContent(): void
    {
        $physio = $this->item(ContentSection::MEDICAL_DEPARTMENT, 'Физиотерапевтическое отделение', null, null, ['priority' => 1]);
        foreach ([
            ['Электролечение', 'Лечебное применение постоянного и импульсного электрического тока.'],
            ['Магнитотерапия', 'Воздействие магнитным полем по назначению врача.'],
            ['Лазеротерапия', 'Физиотерапевтическая процедура с использованием низкоинтенсивного лазерного излучения.'],
        ] as $priority => [$title, $description]) {
            $service = $this->item(ContentSection::MEDICAL_SERVICE, $title, null, $physio, ['description' => $description, 'url' => 'https://shop.hotbot.ai/', 'onlineBooking' => true, 'priority' => $priority + 1]);
            if ($service->description === null) $service->description = $description;
            $this->singleFile($service, 'poster-placeholder.svg');
        }
        $diagnostics = $this->item(ContentSection::MEDICAL_DEPARTMENT, 'Функциональная диагностика', null, null, ['priority' => 2]);
        foreach ([['ЭКГ', 'Исследование электрической активности сердца.'], ['Спирометрия', 'Исследование функции внешнего дыхания.']] as $priority => [$title, $description]) {
            $service = $this->item(ContentSection::MEDICAL_SERVICE, $title, null, $diagnostics, ['description' => $description, 'onlineBooking' => true, 'url' => 'https://shop.hotbot.ai/', 'priority' => $priority + 1]);
            if ($service->description === null) $service->description = $description;
            $this->singleFile($service, 'poster-placeholder.svg');
        }

        $this->item(ContentSection::CONTACTS, 'Служба бронирования', null, null, ['phone' => '8 495 859 2214', 'description' => 'putevki@kanikuly-v-aksakovo.ru', 'priority' => 1]);
    }

    private function seedMap(): void
    {
        $plan = $this->em->getRepository(MapPlan::class)->findOneBy(['territory' => 'main']);
        $new = !$plan;
        $plan ??= new MapPlan();
        if ($new) { $this->em->persist($plan); ++$this->created; }
        if ($new || $this->refresh) {
            $plan->title = 'Основная территория и корпус 7';
            $plan->territory = 'main';
            $plan->width = 1600;
            $plan->height = 1000;
            $plan->active = true;
            if (!$new) ++$this->updated;
        }
        $this->singleFile($plan, 'map-development.svg');

        $nodes = [
            'Вход' => [150, 810, 56.03395, 37.60190],
            'Корпус 1' => [380, 650, 56.03418, 37.60235],
            'Медицинский центр' => [690, 580, 56.03440, 37.60295],
            'Ресторан' => [990, 390, 56.03478, 37.60355],
            'Корпус 7' => [1360, 240, 56.03512, 37.60425],
            'Бассейн' => [880, 750, 56.03422, 37.60352],
            'Спортивный центр' => [1220, 790, 56.03420, 37.60420],
        ];
        $mapNodes = [];
        foreach ($nodes as $name => [$x, $y, $lat, $lon]) $mapNodes[$name] = $this->node($plan, $name, $x, $y, $lat, $lon);
        foreach ([
            ['Вход', 'Корпус 1'], ['Корпус 1', 'Медицинский центр'], ['Медицинский центр', 'Ресторан'], ['Ресторан', 'Корпус 7'],
            ['Медицинский центр', 'Бассейн'], ['Бассейн', 'Спортивный центр'],
        ] as [$from, $to]) $this->edge($plan, $mapNodes[$from], $mapNodes[$to]);

        $area = $this->em->getRepository(MapArea::class)->findOneBy(['plan' => $plan, 'title' => 'Основная территория']);
        $areaNew = !$area; $area ??= new MapArea();
        if ($areaNew) { $area->plan = $plan; $area->title = 'Основная территория'; $this->em->persist($area); ++$this->created; }
        if ($areaNew || $this->refresh) {
            $area->active = true;
            $area->replacePoints([['x'=>80,'y'=>900],['x'=>100,'y'=>120],['x'=>1480,'y'=>100],['x'=>1520,'y'=>900]]);
            if (!$areaNew) ++$this->updated;
        }

        $building1 = $this->place($plan, $mapNodes['Корпус 1'], PlaceType::RESIDENTIAL, 'Жилой корпус 1', [
            'buildingNumber' => '1', 'floorCount' => 5, 'roomCount' => 120, 'description' => 'Жилой корпус основной территории.', 'category' => \App\Enum\MapPlaceCategory::RESIDENTIAL, 'routeDrawn' => true, 'priority' => 1,
        ]);
        $building7 = $this->place($plan, $mapNodes['Корпус 7'], PlaceType::RESIDENTIAL, 'Жилой корпус 7', [
            'buildingNumber' => '7', 'floorCount' => 4, 'roomCount' => 80, 'description' => 'Жилой корпус на отдельной территории.', 'category' => \App\Enum\MapPlaceCategory::RESIDENTIAL, 'routeDrawn' => true, 'priority' => 2,
        ]);
        foreach ([$building1, $building7] as $place) {
            $place->area = $area;
            $this->room($place, 'Стандарт', 'Однокомнатный номер с базовым оснащением.', 1);
            $this->room($place, 'Полулюкс', 'Номер повышенной комфортности.', 2);
            $this->room($place, 'Люкс', 'Просторный номер с отдельной гостиной зоной.', 3);
        }
        $medicalPlace = $this->place($plan, $mapNodes['Медицинский центр'], PlaceType::INFRASTRUCTURE, 'Лечебно-административный корпус', [
            'description' => 'Регистратура, кабинеты врачей и лечебные отделения.', 'workingHours' => 'Ежедневно 08:00-19:00', 'phone' => '59-29', 'onlineBooking' => true, 'bookingUrl' => 'https://shop.hotbot.ai/', 'searchAliases' => ['медцентр', 'врач', 'процедуры'], 'category' => \App\Enum\MapPlaceCategory::BUILDINGS, 'routeDrawn' => true, 'priority' => 2,
        ]);
        $diningPlace = $this->place($plan, $mapNodes['Ресторан'], PlaceType::INFRASTRUCTURE, 'Обеденный зал', ['workingHours' => 'Ежедневно', 'searchAliases' => ['ресторан', 'питание', 'столовая'], 'category' => \App\Enum\MapPlaceCategory::RECREATION, 'routeDrawn' => true, 'priority' => 1]);
        $poolPlace = $this->place($plan, $mapNodes['Бассейн'], PlaceType::INFRASTRUCTURE, 'Бассейн', ['workingHours' => 'Ежедневно 08:00-19:00, перерыв 14:00-15:00', 'searchAliases' => ['плавание', 'фитнес'], 'category' => \App\Enum\MapPlaceCategory::SPORT, 'routeDrawn' => true, 'priority' => 1]);
        $sportPlace = $this->place($plan, $mapNodes['Спортивный центр'], PlaceType::INFRASTRUCTURE, 'Спортивный зал', ['workingHours' => 'Ежедневно 10:00-21:00', 'searchAliases' => ['спортзал', 'фитнес', 'боулинг'], 'category' => \App\Enum\MapPlaceCategory::SPORT, 'routeDrawn' => true, 'priority' => 3]);
        foreach ([$medicalPlace, $diningPlace, $poolPlace, $sportPlace] as $place) { $place->area = $area; }

        $terminal = $this->em->getRepository(KioskTerminal::class)->findOneBy(['code' => 'main-kiosk']);
        $terminalNew = !$terminal;
        $terminal ??= new KioskTerminal();
        if ($terminalNew) { $this->em->persist($terminal); ++$this->created; }
        if ($terminalNew || $this->refresh) {
            $terminal->code = 'main-kiosk';
            $terminal->name = 'Главный информационный киоск';
            $terminal->startNode = $mapNodes['Вход'];
            $terminal->area = $area;
            $terminal->active = true;
            if (!$terminalNew) ++$this->updated;
        }
    }

    private function seedOrderedMedia(): void
    {
        $this->orderedMedia(StandbyMedia::class, 'Добро пожаловать в «Каникулы в Аксаково»', 'poster-placeholder.svg', 1);
        $this->orderedMedia(NewsPoster::class, 'Новости и акции', 'poster-placeholder.svg', 1);
        $this->orderedMedia(AnimationPoster::class, 'Анимационная программа', 'poster-placeholder.svg', 1);
        $this->orderedMedia(GalleryMedia::class, 'Территория санатория', 'map-development.svg', 1);
        $this->orderedMedia(GalleryMedia::class, 'Отдых и лечение', 'poster-placeholder.svg', 2);
    }

    private function seedPublicTransport(): void
    {
        $this->publicTransportRoute('271', 1, [
            ['stopName' => 'Аксаково', 'days' => 'Будни', 'times' => ['06:00', '07:00', '08:00']],
            ['stopName' => 'Аксаково', 'days' => 'Выходные', 'times' => ['07:00', '09:00', '11:00']],
            ['stopName' => 'Метро Физтех', 'days' => 'Будни', 'times' => ['07:00', '08:00', '09:00']],
            ['stopName' => 'Метро Физтех', 'days' => 'Выходные', 'times' => ['08:00', '10:00', '12:00']],
        ]);
        $this->publicTransportRoute('70', 2, [
            ['stopName' => 'Аксаково', 'days' => 'Будни', 'times' => ['06:30', '07:30', '08:30']],
            ['stopName' => 'Аксаково', 'days' => 'Выходные', 'times' => ['07:30', '09:30', '11:30']],
            ['stopName' => 'Катуар', 'days' => 'Будни', 'times' => ['07:00', '08:00', '09:00']],
            ['stopName' => 'Катуар', 'days' => 'Выходные', 'times' => ['08:00', '10:00', '12:00']],
        ]);
    }

    /** @param list<array{stopName: string, days: string, times: list<string>}> $schedules */
    private function publicTransportRoute(string $routeNumber, int $priority, array $schedules): void
    {
        $route = $this->em->getRepository(PublicTransportRoute::class)->findOneBy(['routeNumber' => $routeNumber]);
        $new = !$route;
        $route ??= new PublicTransportRoute();
        if ($new) { $route->routeNumber = $routeNumber; $this->em->persist($route); ++$this->created; }
        if ($new || $this->refresh) { $route->priority = $priority; $route->active = true; $route->setSchedules($schedules); if (!$new) ++$this->updated; }
        if ($route->getSchedules() === []) $route->setSchedules($schedules);
        if ($route->getFileName() === null) {
            $sourceFile = 'document-placeholder.pdf';
            $source = $this->projectDir.'/resources/seed/'.$sourceFile;
            $tempDir = $this->projectDir.'/var/seed-upload';
            if (!is_dir($tempDir)) mkdir($tempDir, 0775, true);
            $temp = $tempDir.'/'.uniqid('public-transport-', true).'-'.$sourceFile;
            if (!copy($source, $temp)) throw new \RuntimeException('Не удалось подготовить схему маршрута '.$routeNumber);
            $route->setFile(new UploadedFile($temp, $sourceFile, 'application/pdf', null, true));
        }
    }

    /** @param class-string $class */
    private function orderedMedia(string $class, string $title, string $sourceFile, int $priority): object
    {
        $media = $this->em->getRepository($class)->findOneBy(['title' => $title]); $new = !$media; $media ??= new $class();
        if ($new) { $media->title = $title; $this->em->persist($media); ++$this->created; }
        if ($new || $this->refresh) { $media->priority = $priority; $media->active = true; if (!$new) ++$this->updated; }
        if ($media->getFileName() === null) {
            $source = $this->projectDir.'/resources/seed/'.$sourceFile; $tempDir = $this->projectDir.'/var/seed-upload'; if (!is_dir($tempDir)) mkdir($tempDir, 0775, true);
            $temp = $tempDir.'/'.uniqid('section-media-', true).'-'.$sourceFile; if (!copy($source, $temp)) throw new \RuntimeException('Не удалось подготовить материал '.$sourceFile);
            $media->setFile(new UploadedFile($temp, $sourceFile, mime_content_type($temp) ?: 'application/octet-stream', null, true));
        }
        return $media;
    }

    private function seedSectionDocuments(): void
    {
        foreach ([
            'Выписка из реестра объектов классификации',
            'Медицинская лицензия',
            'Реквизиты организации',
            'Свидетельство о государственной регистрации',
            'Свидетельство о присвоении категории',
            'Товарный знак «Аксаково»',
            'Товарный знак «Таганрог»',
        ] as $priority => $title) {
            $document = $this->document(SectionSlug::GUEST_INFO, $title, null, [
                'priority' => $priority + 1,
            ]);
            $this->documentFile($document, 'document-placeholder.pdf');
        }

        foreach ([
            SectionSlug::UAV_ALERT->value => 'Памятка о действиях при угрозе атаки БПЛА',
        ] as $section => $title) {
            $document = $this->document(SectionSlug::from($section), $title, null, [
                'priority' => 1,
                'description' => 'Стартовый файл необходимо заменить утверждённым PNG, JPG или PDF прямо в форме раздела.',
            ]);
            $this->documentFile($document, 'memo-placeholder.svg');
        }

        foreach ([
            'Путёвки',
            'Оздоровительные программы',
            'Медицинские услуги',
            'Дополнительные услуги',
            'Ногтевая студия',
            'Косметология',
        ] as $priority => $title) {
            $category = $this->document(SectionSlug::PRICES, $title, null, [
                'priority' => 1,
            ]);
            $category->priority = $priority + 1;
            $this->documentFile($category, 'document-placeholder.pdf');
        }
    }

    /** @param array<string, mixed> $values */
    private function page(ContentPageType $type, array $values): ContentPage
    {
        $page = $this->em->getRepository(ContentPage::class)->findOneBy(['type' => $type]);
        $new = !$page;
        $page ??= new ContentPage();
        if ($new) { $page->type = $type; $this->em->persist($page); ++$this->created; }
        if ($new || $this->refresh) { $this->assign($page, $values); if (!$new) ++$this->updated; }
        return $page;
    }

    /** @param array<string, mixed> $values */
    private function item(ContentSection $section, string $title, ?string $territory = null, ?ContentItem $parent = null, array $values = []): ContentItem
    {
        $criteria = ['section' => $section, 'title' => $title, 'territory' => $territory, 'parent' => $parent];
        $item = $parent && !$parent->getId()
            ? null
            : $this->em->getRepository(ContentItem::class)->findOneBy($criteria);
        $new = !$item;
        $item ??= new ContentItem();
        if ($new) {
            $item->section = $section; $item->title = $title; $item->territory = $territory; $item->parent = $parent;
            $this->em->persist($item); ++$this->created;
        }
        if ($new || $this->refresh) { $this->assign($item, $values); if (!$new) ++$this->updated; }
        return $item;
    }

    /** @param array<string, mixed> $values */
    private function document(SectionSlug $section, string $title, ?SectionDocument $parent = null, array $values = []): SectionDocument
    {
        $criteria = ['section' => $section, 'title' => $title, 'parent' => $parent];
        $document = $parent && !$parent->getId()
            ? null
            : $this->em->getRepository(SectionDocument::class)->findOneBy($criteria);
        $new = !$document;
        $document ??= new SectionDocument();
        if ($new) {
            $document->section = $section;
            $document->title = $title;
            $document->parent = $parent;
            $this->em->persist($document);
            ++$this->created;
        }
        if ($new || $this->refresh) {
            $this->assign($document, $values);
            if (!$new) ++$this->updated;
        }

        return $document;
    }

    private function documentFile(SectionDocument $document, string $sourceFile): void
    {
        $currentFile = $document->getFileName();
        $requiresPdfReplacement = str_ends_with(strtolower($sourceFile), '.pdf')
            && $currentFile
            && !str_ends_with(strtolower($currentFile), '.pdf');
        if ($currentFile && !$requiresPdfReplacement) {
            return;
        }

        $source = $this->projectDir.'/resources/seed/'.$sourceFile;
        $tempDir = $this->projectDir.'/var/seed-upload';
        if (!is_dir($tempDir)) mkdir($tempDir, 0775, true);
        $temp = $tempDir.'/'.uniqid('section-document-', true).'-'.$sourceFile;
        if (!copy($source, $temp)) {
            throw new \RuntimeException('Не удалось подготовить стартовый файл раздела '.$sourceFile);
        }
        $mimeType = mime_content_type($temp) ?: 'application/octet-stream';
        $document->setFile(new UploadedFile($temp, $sourceFile, $mimeType, null, true));
    }

    private function singleFile(object $entity, string $sourceFile): void
    {
        if ($entity->getFileName()) return;
        $source = $this->projectDir.'/resources/seed/'.$sourceFile;
        $tempDir = $this->projectDir.'/var/seed-upload';
        if (!is_dir($tempDir)) mkdir($tempDir, 0775, true);
        $temp = $tempDir.'/'.uniqid('seed-', true).'-'.$sourceFile;
        if (!copy($source, $temp)) throw new \RuntimeException('Не удалось подготовить стартовый медиафайл '.$sourceFile);
        $mimeType = mime_content_type($temp) ?: 'application/octet-stream';
        $entity->setFile(new UploadedFile($temp, $sourceFile, $mimeType, null, true));
    }

    private function pageFiles(ContentPage $page, string $image, string $logo): void
    {
        foreach ([[$image, 'getImageFileName', 'setImageFile'], [$logo, 'getLogoFileName', 'setLogoFile']] as [$sourceFile, $getter, $setter]) {
            if ($page->{$getter}()) continue;
            $source = $this->projectDir.'/resources/seed/'.$sourceFile;
            $tempDir = $this->projectDir.'/var/seed-upload';
            if (!is_dir($tempDir)) mkdir($tempDir, 0775, true);
            $temp = $tempDir.'/'.uniqid('page-', true).'-'.$sourceFile;
            if (!copy($source, $temp)) throw new \RuntimeException('Не удалось подготовить файл страницы '.$sourceFile);
            $page->{$setter}(new UploadedFile($temp, $sourceFile, mime_content_type($temp) ?: 'application/octet-stream', null, true));
        }
    }

    private function pageImageFile(ContentPage $page, string $sourceFile): void
    {
        if ($page->getImageFileName()) return;
        $source = $this->projectDir.'/resources/seed/'.$sourceFile;
        $tempDir = $this->projectDir.'/var/seed-upload';
        if (!is_dir($tempDir)) mkdir($tempDir, 0775, true);
        $temp = $tempDir.'/'.uniqid('page-image-', true).'-'.$sourceFile;
        if (!copy($source, $temp)) throw new \RuntimeException('Не удалось подготовить изображение страницы '.$sourceFile);
        $page->setImageFile(new UploadedFile($temp, $sourceFile, mime_content_type($temp) ?: 'application/octet-stream', null, true));
    }

    private function pageDocument(ContentPage $page, string $sourceFile): void
    {
        if ($page->getDocumentFileName()) return;
        $source = $this->projectDir.'/resources/seed/'.$sourceFile;
        $tempDir = $this->projectDir.'/var/seed-upload';
        if (!is_dir($tempDir)) mkdir($tempDir, 0775, true);
        $temp = $tempDir.'/'.uniqid('page-document-', true).'-'.$sourceFile;
        if (!copy($source, $temp)) throw new \RuntimeException('Не удалось подготовить PDF-файл страницы '.$sourceFile);
        $page->setDocumentFile(new UploadedFile($temp, $sourceFile, 'application/pdf', null, true));
    }

    private function pageMascotTwoFile(ContentPage $page, string $sourceFile): void
    {
        if ($page->getMascotTwoFileName()) return;
        $source = $this->projectDir.'/resources/seed/'.$sourceFile;
        $tempDir = $this->projectDir.'/var/seed-upload';
        if (!is_dir($tempDir)) mkdir($tempDir, 0775, true);
        $temp = $tempDir.'/'.uniqid('page-mascot-two-', true).'-'.$sourceFile;
        if (!copy($source, $temp)) throw new \RuntimeException('Не удалось подготовить второе изображение маскота '.$sourceFile);
        $page->setMascotTwoFile(new UploadedFile($temp, $sourceFile, mime_content_type($temp) ?: 'application/octet-stream', null, true));
    }

    private function pageExtraImageFile(ContentPage $page, string $sourceFile): void
    {
        if ($page->getExtraImageFileName()) return;
        $source = $this->projectDir.'/resources/seed/'.$sourceFile;
        $tempDir = $this->projectDir.'/var/seed-upload';
        if (!is_dir($tempDir)) mkdir($tempDir, 0775, true);
        $temp = $tempDir.'/'.uniqid('page-extra-image-', true).'-'.$sourceFile;
        if (!copy($source, $temp)) throw new \RuntimeException('Не удалось подготовить дополнительное изображение '.$sourceFile);
        $page->setExtraImageFile(new UploadedFile($temp, $sourceFile, mime_content_type($temp) ?: 'application/octet-stream', null, true));
    }

    private function pageFifthImageFile(ContentPage $page, string $sourceFile): void
    {
        if ($page->getFifthImageFileName()) return;
        $source = $this->projectDir.'/resources/seed/'.$sourceFile;
        $tempDir = $this->projectDir.'/var/seed-upload';
        if (!is_dir($tempDir)) mkdir($tempDir, 0775, true);
        $temp = $tempDir.'/'.uniqid('page-fifth-image-', true).'-'.$sourceFile;
        if (!copy($source, $temp)) throw new \RuntimeException('Не удалось подготовить пятое изображение '.$sourceFile);
        $page->setFifthImageFile(new UploadedFile($temp, $sourceFile, mime_content_type($temp) ?: 'application/octet-stream', null, true));
    }

    private function node(MapPlan $plan, string $name, float $x, float $y, float $latitude, float $longitude): MapNode
    {
        $node = $plan->getId() ? $this->em->getRepository(MapNode::class)->findOneBy(['plan' => $plan, 'name' => $name]) : null; $new = !$node; $node ??= new MapNode();
        if ($new) { $node->plan = $plan; $node->name = $name; $this->em->persist($node); ++$this->created; }
        if ($new || $this->refresh) { $node->x=$x; $node->y=$y; $node->latitude=$latitude; $node->longitude=$longitude; $node->active=true; if (!$new) ++$this->updated; }
        return $node;
    }

    private function edge(MapPlan $plan, MapNode $from, MapNode $to): MapEdge
    {
        $edge = $plan->getId() && $from->getId() && $to->getId() ? $this->em->getRepository(MapEdge::class)->findOneBy(['plan' => $plan, 'fromNode' => $from, 'toNode' => $to]) : null; $new = !$edge; $edge ??= new MapEdge();
        if ($new) { $edge->plan=$plan; $edge->fromNode=$from; $edge->toNode=$to; $this->em->persist($edge); ++$this->created; }
        if ($new || $this->refresh) { $edge->bidirectional=true; $edge->accessible=true; $edge->distanceMeters=null; $edge->active=true; if (!$new) ++$this->updated; }
        return $edge;
    }

    /** @param array<string, mixed> $values */
    private function place(MapPlan $plan, MapNode $node, PlaceType $type, string $name, array $values): MapPlace
    {
        $place = $plan->getId() ? $this->em->getRepository(MapPlace::class)->findOneBy(['plan' => $plan, 'name' => $name]) : null; $new = !$place; $place ??= new MapPlace();
        if ($new) { $place->plan=$plan; $place->name=$name; $this->em->persist($place); ++$this->created; }
        if ($new || $this->refresh) { $place->node=$node; $place->type=$type; $this->assign($place,$values); $place->active=true; if (!$new) ++$this->updated; }
        if ($place->photos->isEmpty()) {
            $this->placePhoto($place, 'poster-placeholder.svg', 1);
        } else {
            foreach ($place->photos as $photo) {
                if ($photo->getFileName() === null) {
                    $this->preparePlacePhotoFile($photo, 'poster-placeholder.svg');
                    ++$this->updated;
                }
            }
        }
        if ($place->getIconFileName() === null) {
            $this->preparePlaceIconFile($place, 'logo-placeholder.svg');
        }
        return $place;
    }

    private function preparePlaceIconFile(MapPlace $place, string $sourceFile): void
    {
        $source = $this->projectDir.'/resources/seed/'.$sourceFile;
        $tempDir = $this->projectDir.'/var/seed-upload';
        if (!is_dir($tempDir)) mkdir($tempDir, 0775, true);
        $temp = $tempDir.'/'.uniqid('map-place-icon-', true).'-'.$sourceFile;
        if (!copy($source, $temp)) throw new \RuntimeException('Не удалось подготовить иконку объекта '.$sourceFile);
        $mimeType = mime_content_type($temp) ?: 'application/octet-stream';
        $place->setIconFile(new UploadedFile($temp, $sourceFile, $mimeType, null, true));
    }

    private function placePhoto(MapPlace $place, string $sourceFile, int $priority): void
    {
        $photo = new MapPlacePhoto();
        $photo->priority = $priority;
        $place->addPhoto($photo);
        $this->preparePlacePhotoFile($photo, $sourceFile);
        $this->em->persist($photo);
        ++$this->created;
    }

    private function preparePlacePhotoFile(MapPlacePhoto $photo, string $sourceFile): void
    {
        $source = $this->projectDir.'/resources/seed/'.$sourceFile;
        $tempDir = $this->projectDir.'/var/seed-upload';
        if (!is_dir($tempDir)) mkdir($tempDir, 0775, true);
        $temp = $tempDir.'/'.uniqid('map-place-', true).'-'.$sourceFile;
        if (!copy($source, $temp)) throw new \RuntimeException('Не удалось подготовить фотографию объекта '.$sourceFile);
        $mimeType = mime_content_type($temp) ?: 'application/octet-stream';
        $photo->setFile(new UploadedFile($temp, $sourceFile, $mimeType, null, true));
    }

    private function room(MapPlace $place, string $title, string $description, int $priority): RoomCategory
    {
        $room = $this->em->getRepository(RoomCategory::class)->findOneBy(['title' => $title]); $new = !$room; $room ??= new RoomCategory();
        if ($new) { $room->title=$title; $this->em->persist($room); ++$this->created; }
        if ($new || $this->refresh) { $room->description=$description; $room->priority=$priority; if (!$new) ++$this->updated; }
        $place->addRoomCategory($room);
        if ($room->photos->isEmpty()) {
            $photo = new RoomCategoryPhoto();
            $photo->priority = 1;
            $room->addPhoto($photo);
            $this->prepareRoomCategoryPhotoFile($photo, 'poster-placeholder.svg');
            $this->em->persist($photo);
            ++$this->created;
        } else {
            foreach ($room->photos as $photo) {
                if ($photo->getFileName() === null) {
                    $this->prepareRoomCategoryPhotoFile($photo, 'poster-placeholder.svg');
                    ++$this->updated;
                }
            }
        }
        return $room;
    }

    private function prepareRoomCategoryPhotoFile(RoomCategoryPhoto $photo, string $sourceFile): void
    {
        $source = $this->projectDir.'/resources/seed/'.$sourceFile;
        $tempDir = $this->projectDir.'/var/seed-upload';
        if (!is_dir($tempDir)) mkdir($tempDir, 0775, true);
        $temp = $tempDir.'/'.uniqid('room-category-', true).'-'.$sourceFile;
        if (!copy($source, $temp)) throw new \RuntimeException('Не удалось подготовить фотографию категории '.$sourceFile);
        $mimeType = mime_content_type($temp) ?: 'application/octet-stream';
        $photo->setFile(new UploadedFile($temp, $sourceFile, $mimeType, null, true));
    }

    private function prepareTaganrogSliderImageFile(TaganrogSliderImage $image, string $sourceFile): void
    {
        $source = $this->projectDir.'/resources/seed/'.$sourceFile;
        $tempDir = $this->projectDir.'/var/seed-upload';
        if (!is_dir($tempDir)) mkdir($tempDir, 0775, true);
        $temp = $tempDir.'/'.uniqid('taganrog-slider-', true).'-'.$sourceFile;
        if (!copy($source, $temp)) throw new \RuntimeException('Не удалось подготовить фотографию слайдера '.$sourceFile);
        $mimeType = mime_content_type($temp) ?: 'application/octet-stream';
        $image->setFile(new UploadedFile($temp, $sourceFile, $mimeType, null, true));
    }

    /** @param array<string, mixed> $values */
    private function assign(object $entity, array $values): void
    {
        foreach ($values as $property => $value) {
            if (!property_exists($entity, $property)) throw new \LogicException(sprintf('Неизвестное поле %s::%s', $entity::class, $property));
            $entity->{$property} = $value;
        }
    }
}
