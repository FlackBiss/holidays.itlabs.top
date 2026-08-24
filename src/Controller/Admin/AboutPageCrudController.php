<?php

namespace App\Controller\Admin;

use App\Controller\Admin\Field\VichFileField;
use App\Enum\ContentPageType;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class AboutPageCrudController extends AbstractStaticPageCrudController
{
    protected static function pageType(): ContentPageType { return ContentPageType::ABOUT; }
    protected static function pageLabel(): string { return 'О санатории'; }
    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('title', 'Заголовок');
        yield TextEditorField::new('description', 'Описание');
        yield VichFileField::new('imageFile', 'Основное изображение')->onlyOnForms()->setFormTypeOption('allow_delete', true);
        yield VichFileField::new('logoFile', 'Изображение маскота')->onlyOnForms()->setFormTypeOption('allow_delete', true);
        yield ArrayField::new('advantages', 'Преимущества / тезисы');
        yield BooleanField::new('active', 'Показывать');
    }
}
