<?php

namespace App\Controller\Admin;

use App\Controller\Admin\Field\VichFileField;
use App\Entity\MapPlan;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;

final class MapPlanCrudController extends AbstractCrudController
{
    public function __construct(private readonly EntityManagerInterface $em, private readonly AdminUrlGenerator $adminUrlGenerator) {}
    public static function getEntityFqcn(): string { return MapPlan::class; }
    public function configureCrud(Crud $crud): Crud { return $crud->setEntityLabelInPlural('Карта территории')->setEntityLabelInSingular('карта')->setPageTitle(Crud::PAGE_EDIT, 'Карта территории'); }
    public function configureActions(Actions $actions): Actions { return $actions->disable(Action::NEW, Action::DELETE, Action::BATCH_DELETE, Action::DETAIL); }
    public function index(AdminContext $context)
    {
        $plan = $this->em->getRepository(MapPlan::class)->findOneBy(['active' => true]) ?? $this->em->getRepository(MapPlan::class)->findOneBy([]);
        if (!$plan) return parent::index($context);
        $url = $this->adminUrlGenerator->unsetAll()->setDashboard(DashboardController::class)->setController(self::class)->setAction(Action::EDIT)->setEntityId($plan->getId())->generateUrl();
        return $this->redirect($url);
    }
    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('title','Название');
        yield ChoiceField::new('territory','Территория')->setChoices(['Основная территория и корпус 7' => 'main'])->setFormTypeOption('disabled', true);
        yield VichFileField::new('file','Изображение 2,5D')->onlyOnForms()->setFormTypeOption('allow_delete', true)->setHelp('PNG, JPG, WEBP или SVG.');
        yield IntegerField::new('width','Ширина, px'); yield IntegerField::new('height','Высота, px'); yield BooleanField::new('active','Активна');
    }
}
