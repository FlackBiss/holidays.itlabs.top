<?php

namespace App\Controller\Admin;

use App\Controller\Admin\Field\VichFileField;
use App\Enum\ContentPageType;
use App\Form\TaganrogSliderImageType;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;

final class TaganrogPageCrudController extends AbstractStaticPageCrudController
{
    protected static function pageType(): ContentPageType { return ContentPageType::TAGANROG; }
    protected static function pageLabel(): string { return 'Каникулы в Таганроге'; }
    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('title', 'Заголовок')->setColumns(12);
        yield VichFileField::new('imageFile', 'QR-код сайта')->onlyOnForms()->setFormTypeOption('allow_delete', true)->setColumns(12);
        yield VichFileField::new('logoFile', 'Логотип')->onlyOnForms()->setFormTypeOption('allow_delete', true)->setColumns(12);
        yield VichFileField::new('mascotTwoFile', 'Изображение 1')->onlyOnForms()->setFormTypeOption('allow_delete', true)->setColumns(12);
        yield VichFileField::new('extraImageFile', 'Изображение 2')->onlyOnForms()->setFormTypeOption('allow_delete', true)->setColumns(12);
        yield VichFileField::new('fifthImageFile', 'Изображение 3')->onlyOnForms()->setFormTypeOption('allow_delete', true)->setColumns(12);
        yield CollectionField::new('sliderImages', 'Фотографии слайдера')
            ->setEntryType(TaganrogSliderImageType::class)
            ->allowAdd()->allowDelete()
            ->setFormTypeOption('by_reference', false)
            ->setHelp('Добавьте любое количество фотографий и задайте порядок их показа.')
            ->onlyOnForms()->setColumns(12);
        yield TextEditorField::new('mission', 'Наша миссия')->setColumns(12);
        yield TextEditorField::new('description', 'Описание')->setColumns(12);
        yield ArrayField::new('aboutSanatorium', 'О санатории')->setColumns(12);
    }
}
