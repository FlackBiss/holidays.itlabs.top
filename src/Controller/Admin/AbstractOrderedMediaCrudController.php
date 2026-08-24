<?php

namespace App\Controller\Admin;

use App\Controller\Admin\Field\VichFileField;
use App\Enum\MediaType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;

abstract class AbstractOrderedMediaCrudController extends AbstractCrudController
{
    abstract protected static function label(): string;
    protected static function verticalLayout(): bool { return false; }
    protected static function imageOnly(): bool { return false; }
    public function configureCrud(Crud $crud): Crud { return $crud->setEntityLabelInPlural(static::label())->setEntityLabelInSingular('материал')->setDefaultSort(['priority' => 'ASC', 'id' => 'ASC']); }
    public function configureFilters(Filters $filters): Filters { return $filters->add(TextFilter::new('title', 'Название'))->add(ChoiceFilter::new('type', 'Тип')->setChoices(MediaType::choices()))->add(BooleanFilter::new('active', 'Показывать')); }
    public function configureFields(string $pageName): iterable
    {
        $columns = static::verticalLayout() ? 12 : 8;
        yield TextField::new('title', 'Название')->setColumns($columns);
        $file = VichFileField::new('file', static::imageOnly() ? 'Изображение-постер' : 'Изображение или видео')
            ->onlyOnForms()
            ->setRequired($pageName === Crud::PAGE_NEW)
            ->setFormTypeOption('allow_delete', true)
            ->setHelp(static::imageOnly() ? 'JPG, PNG, WEBP или SVG.' : 'JPG, PNG, WEBP, SVG, MP4 или WEBM; тип определяется автоматически.')
            ->setColumns($columns);
        if (static::imageOnly()) $file->setFormTypeOption('attr.accept', 'image/jpeg,image/png,image/webp,image/svg+xml');
        yield $file;
        yield TextField::new('url', 'Файл')->onlyOnIndex();
        yield TextField::new('typeLabel', 'Тип')->onlyOnIndex();
        yield IntegerField::new('priority', 'Порядок отображения')->setColumns(static::verticalLayout() ? 12 : 4);
        yield BooleanField::new('active', 'Показывать')->setColumns(static::verticalLayout() ? 12 : 4);
    }
}
