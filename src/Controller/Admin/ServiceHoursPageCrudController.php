<?php

namespace App\Controller\Admin;

use App\Controller\Admin\Field\VichFileField;
use App\Enum\ContentPageType;
use App\Form\ServiceQrLinkType;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class ServiceHoursPageCrudController extends AbstractStaticPageCrudController
{
    protected static function pageType(): ContentPageType { return ContentPageType::SERVICE_HOURS; }
    protected static function pageLabel(): string { return 'Контакты'; }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addTab('Основное');
        yield TextField::new('title', 'Название')->setColumns(12);
        yield VichFileField::new('documentFile', 'PDF-файл с полным графиком работы')->onlyOnForms()->setFormTypeOption('allow_delete', true)->setColumns(12);
        yield FormField::addTab('Службы');
        yield TextEditorField::new('receptionDescription', 'Ресепшн')->setColumns(12);
        yield TextEditorField::new('registryDescription', 'Медицинский центр (регистратура)')->setColumns(12);
        yield TextEditorField::new('nurseDescription', 'Дежурная медицинская сестра')->setColumns(12);
        yield FormField::addTab('QR-коды');
        yield CollectionField::new('serviceQrLinks', 'QR-коды')
            ->setEntryType(ServiceQrLinkType::class)
            ->allowAdd()->allowDelete()->setFormTypeOption('by_reference', false)
            ->setHelp('Для каждого QR-кода укажите название, описание и порядок отображения.')
            ->onlyOnForms()->setColumns(12);
        yield BooleanField::new('active', 'Показывать раздел');
    }
}
