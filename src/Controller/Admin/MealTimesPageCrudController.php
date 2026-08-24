<?php

namespace App\Controller\Admin;

use App\Controller\Admin\Field\VichFileField;
use App\Enum\ContentPageType;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;

final class MealTimesPageCrudController extends AbstractStaticPageCrudController
{
    protected static function pageType(): ContentPageType { return ContentPageType::MEAL_TIMES; }
    protected static function pageLabel(): string { return 'Время питания'; }

    public function configureFields(string $pageName): iterable
    {
        yield VichFileField::new('imageFile', 'Изображение ежа')->onlyOnForms()->setFormTypeOption('allow_delete', true)->setColumns(12);
        yield TextEditorField::new('mainTerritoryDiningHallDescription', 'Время работы обеденного зала основной территории')->setColumns(12);
        yield TextEditorField::new('buildingSevenDiningHallDescription', 'Время работы обеденного зала территории 7 корпуса')->setColumns(12);
        yield VichFileField::new('logoFile', 'Изображение обеденных залов')->onlyOnForms()->setFormTypeOption('allow_delete', true)->setColumns(12);
        yield TextEditorField::new('cafeDescription', 'Время работы кафе (Бассейн, 2 этаж)')->setColumns(12);
        yield VichFileField::new('mascotTwoFile', 'Изображение кафе (Бассейн, 2 этаж)')->onlyOnForms()->setFormTypeOption('allow_delete', true)->setColumns(12);
        yield TextEditorField::new('phytoBarDescription', 'Время работы фитобара (Бассейн, 2 этаж)')->setColumns(12);
        yield VichFileField::new('extraImageFile', 'Изображение фитобара (Бассейн, 2 этаж)')->onlyOnForms()->setFormTypeOption('allow_delete', true)->setColumns(12);
    }
}
