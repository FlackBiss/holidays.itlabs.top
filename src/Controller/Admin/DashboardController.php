<?php

namespace App\Controller\Admin;

use App\Entity\{AnimationPoster,ContentItem,ContentPage,GalleryMedia,KioskTerminal,MapPlace,MapPlan,NewsPoster,RoomCategory,SectionDocument,SiteSettings,StandbyMedia,User};
use EasyCorp\Bundle\EasyAdminBundle\Config\{Dashboard,MenuItem};
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractDashboardController
{
    #[Route('/admin', name: 'admin')]
    public function index(): Response { return $this->redirectToRoute('admin_site_settings_index'); }
    public function configureDashboard(): Dashboard { return Dashboard::new()->setTitle('Каникулы в Аксаково')->setFaviconPath('favicon.ico')->renderContentMaximized()->generateRelativeUrls(); }
    public function configureMenuItems(): iterable
    {
        yield MenuItem::section('Контент');
        yield MenuItem::linkToCrud('Режим ожидания', 'fas fa-display', StandbyMedia::class)->setController(StandbyMediaCrudController::class);
        yield MenuItem::subMenu('Раздел «О санатории»', 'fas fa-circle-info')->setSubItems([
            MenuItem::linkToCrud('Основная информация', 'fas fa-circle-info', ContentPage::class)->setController(AboutPageCrudController::class),
            MenuItem::linkToCrud('Информация для гостей — описание', 'fas fa-align-left', ContentPage::class)->setController(GuestInfoPageCrudController::class),
            MenuItem::linkToCrud('Информация для гостей — документы', 'fas fa-file-circle-check', SectionDocument::class)->setController(GuestDocumentsCrudController::class),
            MenuItem::linkToCrud('Контакты', 'fas fa-address-book', ContentPage::class)->setController(ServiceHoursPageCrudController::class),
            MenuItem::linkToCrud('Стоимость услуг', 'fas fa-ruble-sign', SectionDocument::class)->setController(PriceDocumentsCrudController::class),
            MenuItem::linkToCrud('Фотогалерея', 'fas fa-images', GalleryMedia::class)->setController(GalleryMediaCrudController::class),
            MenuItem::linkToCrud('Медицинский центр — оформление', 'fas fa-image', ContentPage::class)->setController(MedicalCenterPageCrudController::class),
            MenuItem::linkToCrud('Медицинский центр — разделы', 'fas fa-folder-tree', ContentItem::class)->setController(MedicalDepartmentCrudController::class),
            MenuItem::linkToCrud('Медицинский центр — услуги', 'fas fa-user-doctor', ContentItem::class)->setController(MedicalServiceCrudController::class),
            MenuItem::linkToCrud('Инфраструктура', 'fas fa-list', ContentPage::class)->setController(InfrastructurePageCrudController::class),
            MenuItem::linkToCrud('Новости и акции', 'fas fa-bullhorn', NewsPoster::class)->setController(NewsPosterCrudController::class),
            MenuItem::linkToCrud('Анимационная программа', 'fas fa-masks-theater', AnimationPoster::class)->setController(AnimationPosterCrudController::class),
            MenuItem::linkToCrud('Время питания', 'fas fa-utensils', ContentPage::class)->setController(MealTimesPageCrudController::class),
            MenuItem::linkToCrud('Программа «Подключайся»', 'fas fa-gift', ContentPage::class)->setController(ConnectPageCrudController::class),
            MenuItem::linkToCrud('Правила проживания', 'fas fa-shield-halved', ContentPage::class)->setController(ResidenceRulesPageCrudController::class),
            MenuItem::linkToCrud('Действия при угрозе атаки БПЛА', 'fas fa-triangle-exclamation', SectionDocument::class)->setController(UavAlertDocumentCrudController::class),
            MenuItem::linkToCrud('Каникулы в Таганроге', 'fas fa-umbrella-beach', ContentPage::class)->setController(TaganrogPageCrudController::class),
        ]);
        yield MenuItem::subMenu('Транспорт и услуги', 'fas fa-bus')->setSubItems([
            MenuItem::linkToCrud('Общественный транспорт', 'fas fa-route', \App\Entity\PublicTransportRoute::class)->setController(PublicTransportRouteCrudController::class),
            MenuItem::linkToCrud('Трансфер между территориями', 'fas fa-bus', ContentPage::class)->setController(TransferPageCrudController::class),
        ]);
        yield MenuItem::section('Карта и навигация');
        yield MenuItem::linkToCrud('Карта территории','fas fa-map',MapPlan::class)->setController(MapPlanCrudController::class);
        yield MenuItem::linkToRoute('Расчерчивание карты', 'fas fa-draw-polygon', 'admin_map_tracer_index');
        yield MenuItem::linkToCrud('Категории номеров', 'fas fa-bed', RoomCategory::class)->setController(RoomCategoryCrudController::class);
        yield MenuItem::subMenu('Объекты карты', 'fas fa-location-dot')->setSubItems([
            MenuItem::linkToCrud('Все объекты', 'fas fa-list', MapPlace::class)->setController(MapPlaceCrudController::class),
            MenuItem::linkToCrud('Жилые корпуса', 'fas fa-building', MapPlace::class)->setController(ResidentialMapPlaceCrudController::class),
            MenuItem::linkToCrud('Инфраструктурные объекты', 'fas fa-tree-city', MapPlace::class)->setController(InfrastructureMapPlaceCrudController::class),
        ]);
        yield MenuItem::linkToCrud('Терминалы','fas fa-display',KioskTerminal::class)->setController(KioskTerminalCrudController::class);
        yield MenuItem::section('Настройки');
        yield MenuItem::linkToCrud('Общие настройки','fas fa-gear',SiteSettings::class)->setController(SiteSettingsCrudController::class);
        yield MenuItem::linkToCrud('Пользователи','fas fa-user-gear',User::class)->setPermission('ROLE_ADMIN');
        yield MenuItem::linkToUrl('Документация API','fas fa-code','/api/docs.html')->setLinkTarget('_blank')->setPermission('ROLE_ADMIN');
    }
}
