<?php

namespace App\Controller\Admin;

use App\Entity\RoomCategory;
use App\Form\RoomCategoryPhotoType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;

final class RoomCategoryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string { return RoomCategory::class; }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('категорию номера')
            ->setEntityLabelInPlural('Категории номеров')
            ->setDefaultSort(['priority' => 'ASC', 'title' => 'ASC']);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add(TextFilter::new('title', 'Название'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('title', 'Название')->setColumns(12);
        yield TextEditorField::new('description', 'Описание')->hideOnIndex()->setColumns(12);
        yield IntegerField::new('priority', 'Порядок отображения')->setColumns(12);
        yield AssociationField::new('places', 'Используется в корпусах')->onlyOnIndex();
        yield CollectionField::new('photos', 'Фотографии')
            ->setEntryType(RoomCategoryPhotoType::class)
            ->setEntryIsComplex()
            ->renderExpanded()
            ->allowAdd()->allowDelete()
            ->setFormTypeOption('by_reference', false)
            ->setHelp('Загрузите фотографии категории и укажите порядок их показа.')
            ->onlyOnForms()->setColumns(12);
    }
}
