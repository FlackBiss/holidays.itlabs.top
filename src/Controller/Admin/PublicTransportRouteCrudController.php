<?php

namespace App\Controller\Admin;

use App\Controller\Admin\Field\VichFileField;
use App\Entity\PublicTransportRoute;
use App\Form\PublicTransportScheduleType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;

final class PublicTransportRouteCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string { return PublicTransportRoute::class; }
    public function configureCrud(Crud $crud): Crud { return $crud->setEntityLabelInSingular('Автобус')->setEntityLabelInPlural('Общественный транспорт')->setDefaultSort(['priority' => 'ASC', 'routeNumber' => 'ASC'])->setSearchFields(['routeNumber']); }
    public function configureFilters(Filters $filters): Filters { return $filters->add(TextFilter::new('routeNumber', 'Номер автобуса'))->add(BooleanFilter::new('active', 'Показывать')); }
    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('routeNumber', 'Номер автобуса')->setColumns(12);
        yield VichFileField::new('file', 'Схема маршрута (изображение или PDF)')->onlyOnForms()->setFormTypeOption('allow_delete', true)->setColumns(12);
        yield TextField::new('routeMapUrl', 'Схема маршрута')->onlyOnIndex();
        yield CollectionField::new('schedules', 'Расписание по остановкам')->setEntryType(PublicTransportScheduleType::class)->setEntryIsComplex()->renderExpanded()->allowAdd()->allowDelete()->setFormTypeOption('by_reference', false)->onlyOnForms()->setColumns(12);
        yield IntegerField::new('priority', 'Порядок отображения')->setColumns(12);
        yield BooleanField::new('active', 'Показывать')->setColumns(12);
    }
}
