<?php

namespace App\Controller\Admin;

use App\Enum\ContentPageType;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;

final class GuestInfoPageCrudController extends AbstractStaticPageCrudController
{
    protected static function pageType(): ContentPageType { return ContentPageType::GUEST_INFO; }
    protected static function pageLabel(): string { return 'Информация для гостей'; }
    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('title', 'Заголовок');
        yield TextEditorField::new('description', 'Описание раздела');
        yield UrlField::new('sourceUrl', 'Ссылка на полную информацию');
        yield BooleanField::new('active', 'Показывать');
    }
}
