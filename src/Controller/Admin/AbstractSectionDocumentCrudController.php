<?php

namespace App\Controller\Admin;

use App\Controller\Admin\Field\VichFileField;
use App\Entity\SectionDocument;
use App\Enum\SectionSlug;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractSectionDocumentCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AdminUrlGenerator $adminUrlGenerator,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return SectionDocument::class;
    }

    abstract protected static function section(): SectionSlug;
    abstract protected static function label(): string;

    protected static function singleton(): bool
    {
        return false;
    }

    protected static function fixedCollection(): bool { return false; }

    protected static function hasDescription(): bool { return true; }

    protected static function verticalFields(): bool { return false; }

    protected static function pdfOnly(): bool
    {
        return in_array(static::section(), [SectionSlug::GUEST_INFO, SectionSlug::TRANSFER, SectionSlug::PUBLIC_TRANSPORT, SectionSlug::PRICES], true);
    }

    protected static function defaultTitle(): string
    {
        return static::label();
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInPlural(static::label())
            ->setEntityLabelInSingular(static::label())
            ->setPageTitle(Crud::PAGE_INDEX, static::label())
            ->setPageTitle(Crud::PAGE_EDIT, static::label())
            ->setDefaultSort(['priority' => 'ASC', 'id' => 'ASC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions->disable(Action::DETAIL);
        if (static::singleton() || static::fixedCollection()) $actions->disable(Action::NEW, Action::DELETE, Action::BATCH_DELETE);
        return $actions;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add(TextFilter::new('title', 'Название'))->add(BooleanFilter::new('active', 'Показывать'));
    }

    public function configureFields(string $pageName): iterable
    {
        $columns = static::verticalFields() ? 12 : 8;
        yield TextField::new('title', 'Название')
            ->setFormTypeOption('disabled', static::singleton() || static::fixedCollection())
            ->setColumns($columns);
        if (!static::singleton() && static::hasDescription()) yield TextEditorField::new('description', 'Описание')->hideOnIndex()->setColumns($columns);
        $file = VichFileField::new('file', static::pdfOnly() ? 'PDF-файл' : 'PDF или изображение')
            ->onlyOnForms()
            ->setRequired(false)
            ->setFormTypeOption('allow_delete', true)
            ->setColumns($columns);
        if (static::pdfOnly()) $file->setFormTypeOption('attr.accept', 'application/pdf');
        yield $file;
        if (!static::singleton()) {
            yield IntegerField::new('priority', 'Порядок отображения')->setColumns(static::verticalFields() ? 12 : 4);
            yield BooleanField::new('active', 'Показывать')->setColumns(static::verticalFields() ? 12 : 4);
        }
        yield UrlField::new('url', 'Текущий файл')->onlyOnIndex();
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        return parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters)
            ->andWhere('entity.section = :documentSection')
            ->andWhere('entity.parent IS NULL')
            ->setParameter('documentSection', static::section());
    }

    public function index(AdminContext $context)
    {
        if (!static::singleton()) {
            return parent::index($context);
        }

        $document = $this->em->getRepository(SectionDocument::class)->findOneBy([
            'section' => static::section(),
            'parent' => null,
        ]);
        if (!$document instanceof SectionDocument) {
            $document = new SectionDocument();
            $document->section = static::section();
            $document->title = static::defaultTitle();
            $this->em->persist($document);
            $this->em->flush();
        }

        $url = $this->adminUrlGenerator
            ->unsetAll()
            ->setDashboard(DashboardController::class)
            ->setController(static::class)
            ->setAction(Action::EDIT)
            ->setEntityId($document->getId())
            ->generateUrl();

        return $this->redirect($url);
    }

    public function edit(AdminContext $context): KeyValueStore|Response
    {
        $document = $context->getEntity()->getInstance();
        if (!$document instanceof SectionDocument || $document->section !== static::section()) {
            throw $this->createNotFoundException('Документ не относится к этому разделу.');
        }

        return parent::edit($context);
    }

    public function persistEntity(EntityManagerInterface $entityManager, mixed $entityInstance): void
    {
        $this->normalize($entityInstance);
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, mixed $entityInstance): void
    {
        $this->normalize($entityInstance);
        parent::updateEntity($entityManager, $entityInstance);
    }

    private function normalize(mixed $entity): void
    {
        if ($entity instanceof SectionDocument) {
            $entity->section = static::section();
            $entity->parent = null;
            if (static::singleton() || static::fixedCollection()) $entity->active = true;
        }
    }
}
