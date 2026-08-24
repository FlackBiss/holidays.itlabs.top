<?php

namespace App\Controller\Admin;

use App\Controller\Admin\Field\VichFileField;
use App\Entity\ContentItem;
use App\Enum\ContentSection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;

abstract class AbstractSectionItemCrudController extends AbstractCrudController
{
    abstract protected static function section(): ContentSection;
    abstract protected static function label(): string;

    public static function getEntityFqcn(): string { return ContentItem::class; }
    public function configureCrud(Crud $crud): Crud { return $crud->setEntityLabelInPlural(static::label())->setEntityLabelInSingular('запись')->setDefaultSort(['priority' => 'ASC', 'id' => 'ASC']); }
    public function configureFilters(Filters $filters): Filters { return $filters->add(TextFilter::new('title', 'Название'))->add(TextFilter::new('territory', 'Территория'))->add(BooleanFilter::new('active', 'Показывать')); }

    public function configureFields(string $pageName): iterable
    {
        $section = static::section();
        if ($section === ContentSection::MEDICAL_SERVICE) {
            $parentSection = ContentSection::MEDICAL_DEPARTMENT;
            yield AssociationField::new('parent', 'Родительская запись')->setQueryBuilder(
                static fn (QueryBuilder $qb): QueryBuilder => $qb->andWhere('entity.section = :parentSection')->andWhere('entity.parent IS NULL')->setParameter('parentSection', $parentSection),
            )->setColumns(8);
        }
        yield TextField::new('title', 'Название')->setColumns(8);
        if (in_array($section, [ContentSection::MEDICAL_DEPARTMENT, ContentSection::MEDICAL_SERVICE, ContentSection::CONTACTS], true)) {
            yield TextEditorField::new('description', 'Описание')->hideOnIndex()->setColumns(8);
        }
        if ($section === ContentSection::CONTACTS) {
            yield TextField::new('phone', 'Телефон')->setColumns(5);
        }
        if ($section === ContentSection::MEDICAL_SERVICE) {
            yield BooleanField::new('onlineBooking', 'Можно записаться онлайн');
            yield UrlField::new('url', 'Ссылка на запись')->setColumns(8);
        }
        if ($section === ContentSection::MEDICAL_SERVICE) {
            yield VichFileField::new('file', 'Фото услуги')->onlyOnForms()->setFormTypeOption('allow_delete', true)->setColumns(8);
            yield TextField::new('fileUrl', 'Файл')->onlyOnIndex();
        }
        yield IntegerField::new('priority', 'Порядок отображения')->setColumns(4);
        yield BooleanField::new('active', 'Показывать');
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        return parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters)
            ->andWhere('entity.section = :fixedSection')->setParameter('fixedSection', static::section());
    }

    public function persistEntity(EntityManagerInterface $entityManager, mixed $entityInstance): void
    {
        if ($entityInstance instanceof ContentItem) $entityInstance->section = static::section();
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, mixed $entityInstance): void
    {
        if ($entityInstance instanceof ContentItem) $entityInstance->section = static::section();
        parent::updateEntity($entityManager, $entityInstance);
    }
}
