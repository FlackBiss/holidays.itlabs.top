<?php

namespace App\Controller\Admin;

use App\Controller\Admin\Field\VichFileField;
use App\Entity\SiteSettings;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

final class SiteSettingsCrudController extends AbstractCrudController
{
    public function __construct(private readonly EntityManagerInterface $em, private readonly AdminUrlGenerator $adminUrlGenerator) {}
    public static function getEntityFqcn(): string { return SiteSettings::class; }
    public function configureCrud(Crud $crud): Crud { return $crud->setEntityLabelInPlural('Общие настройки')->setEntityLabelInSingular('настройки'); }
    public function configureActions(Actions $actions): Actions { return $actions->disable(Action::NEW, Action::DELETE, Action::BATCH_DELETE, Action::DETAIL); }
    public function index(AdminContext $context)
    {
        $settings = $this->em->getRepository(SiteSettings::class)->findOneBy(['code' => 'main']) ?? $this->em->getRepository(SiteSettings::class)->findOneBy([]);
        if (!$settings) return parent::index($context);
        $url = $this->adminUrlGenerator->unsetAll()->setDashboard(DashboardController::class)->setController(self::class)->setAction(Action::EDIT)->setEntityId($settings->getId())->generateUrl();
        return $this->redirect($url);
    }
    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('companyName','Название')->setColumns(8);
        yield VichFileField::new('file','Логотип')->onlyOnForms()->setFormTypeOption('allow_delete', true)->setHelp('PNG, JPG, WEBP или SVG.')->setColumns(8);
        yield TextField::new('fileUrl','Логотип')->onlyOnIndex();
        yield NumberField::new('latitude','Широта погоды')->setNumDecimals(7)->setColumns(4);
        yield NumberField::new('longitude','Долгота погоды')->setNumDecimals(7)->setColumns(4);
        yield IntegerField::new('weatherCacheTtl','Кэш погоды, сек')->setColumns(4);
        yield IntegerField::new('idleTimeoutSeconds','Переход в ожидание, сек')->setColumns(4);
        yield IntegerField::new('modalTimeoutSeconds','Время до появления модального окна')->setColumns(4);
        yield IntegerField::new('slideDurationSeconds','Показ слайда, сек')->setColumns(4);
        yield TextField::new('plainExitPassword', 'Новый пароль для выхода из приложения')
            ->setFormType(PasswordType::class)
            ->setRequired(false)
            ->setFormTypeOption('attr', ['autocomplete' => 'new-password'])
            ->setHelp('Минимум 4 символа. Оставьте пустым, чтобы сохранить текущий пароль.')
            ->onlyOnForms()
            ->setColumns(4);
        yield UrlField::new('mobileMapUrl','Адрес мобильной карты')->setColumns(8);
        yield IntegerField::new('maxGeoSnapDistanceMeters','Макс. расстояние до сети, м')->setColumns(4);
        yield ArrayField::new('allowedLinks', 'Разрешённые ссылки')
            ->setHelp('Добавьте каждую строку отдельным элементом.')
            ->setColumns(8);
    }

    public function persistEntity(EntityManagerInterface $entityManager, mixed $entityInstance): void
    {
        if ($entityInstance instanceof SiteSettings) $this->applyExitPassword($entityInstance);
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, mixed $entityInstance): void
    {
        if ($entityInstance instanceof SiteSettings) $this->applyExitPassword($entityInstance);
        parent::updateEntity($entityManager, $entityInstance);
    }

    private function applyExitPassword(SiteSettings $settings): void
    {
        $password = $settings->getPlainExitPassword();
        if ($password !== null) $settings->setExitPassword($password);
    }
}
