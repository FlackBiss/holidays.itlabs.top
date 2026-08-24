<?php

namespace App\Controller\Admin;

use App\Entity\ContentPage;
use App\Enum\ContentPageType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;

abstract class AbstractStaticPageCrudController extends AbstractCrudController
{
    public function __construct(private readonly EntityManagerInterface $em, private readonly AdminUrlGenerator $adminUrlGenerator) {}
    public static function getEntityFqcn(): string { return ContentPage::class; }
    abstract protected static function pageType(): ContentPageType;
    abstract protected static function pageLabel(): string;

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setEntityLabelInPlural(static::pageLabel())->setEntityLabelInSingular(static::pageLabel());
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->disable(Action::NEW, Action::DELETE, Action::BATCH_DELETE, Action::DETAIL);
    }

    public function index(AdminContext $context)
    {
        $page = $this->em->getRepository(ContentPage::class)->findOneBy(['type' => static::pageType()]);
        if (!$page) return parent::index($context);
        $url = $this->adminUrlGenerator->unsetAll()->setDashboard(DashboardController::class)->setController(static::class)->setAction(Action::EDIT)->setEntityId($page->getId())->generateUrl();
        return $this->redirect($url);
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        return parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters)
            ->andWhere('entity.type = :pageType')
            ->setParameter('pageType', static::pageType());
    }

    public function persistEntity(EntityManagerInterface $entityManager, mixed $entityInstance): void
    {
        if ($entityInstance instanceof ContentPage) $entityInstance->type = static::pageType();
        parent::persistEntity($entityManager, $entityInstance);
    }
}
