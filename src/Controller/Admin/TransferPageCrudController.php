<?php

namespace App\Controller\Admin;

use App\Controller\Admin\Field\VichFileField;
use App\Enum\ContentPageType;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;

final class TransferPageCrudController extends AbstractStaticPageCrudController
{
    protected static function pageType(): ContentPageType { return ContentPageType::TRANSFER; }
    protected static function pageLabel(): string { return 'Трансфер между территориями'; }

    public function configureFields(string $pageName): iterable
    {
        yield VichFileField::new('imageFile', 'Карта трансфера')->onlyOnForms()->setFormTypeOption('allow_delete', true)->setColumns(12);
        yield ArrayField::new('mainTerritoryDepartureTimes', 'Отправление с основной территории')->setColumns(12);
        yield ArrayField::new('buildingSevenDepartureTimes', 'Отправление с территории 7 корпуса')->setColumns(12);
    }
}
