<?php

namespace App\Controller\Admin;

use App\Controller\Admin\Field\VichFileField;
use App\Enum\ContentPageType;
use App\Form\ConnectRewardType;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class ConnectPageCrudController extends AbstractStaticPageCrudController
{
    protected static function pageType(): ContentPageType { return ContentPageType::CONNECT; }
    protected static function pageLabel(): string { return 'Программа «Подключайся»'; }
    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('title', 'Заголовок')->setColumns(12);
        yield VichFileField::new('imageFile', 'Изображение страницы')->onlyOnForms()->setFormTypeOption('allow_delete', true)->setColumns(12);
        yield VichFileField::new('logoFile', 'Логотип «Подключайся»')->onlyOnForms()->setFormTypeOption('allow_delete', true)->setColumns(12);
        yield ArrayField::new('prizeBenefits', 'Хотите получить призы...')->setColumns(12);
        yield ArrayField::new('importantNotices', 'Важно!')->setColumns(12);
        yield CollectionField::new('connectRewards', 'Разбалловка достижений')
            ->setEntryType(ConnectRewardType::class)
            ->setEntryIsComplex()
            ->renderExpanded()
            ->allowAdd()->allowDelete()
            ->setFormTypeOption('by_reference', false)
            ->setHelp('Для каждого достижения задайте от 1 до 5 баллов.')
            ->onlyOnForms()->setColumns(12);
    }
}
