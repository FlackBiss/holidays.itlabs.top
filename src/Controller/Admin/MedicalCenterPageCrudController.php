<?php

namespace App\Controller\Admin;

use App\Controller\Admin\Field\VichFileField;
use App\Enum\ContentPageType;

final class MedicalCenterPageCrudController extends AbstractStaticPageCrudController
{
    protected static function pageType(): ContentPageType { return ContentPageType::MEDICAL_CENTER; }
    protected static function pageLabel(): string { return 'Медицинский центр — оформление'; }

    public function configureFields(string $pageName): iterable
    {
        yield VichFileField::new('imageFile', 'Изображение страницы')
            ->onlyOnForms()->setFormTypeOption('allow_delete', true)->setColumns(12);
        yield VichFileField::new('logoFile', 'Маскот №1')
            ->onlyOnForms()->setFormTypeOption('allow_delete', true)->setColumns(12);
        yield VichFileField::new('mascotTwoFile', 'Маскот №2')
            ->onlyOnForms()->setFormTypeOption('allow_delete', true)->setColumns(12);
    }
}
