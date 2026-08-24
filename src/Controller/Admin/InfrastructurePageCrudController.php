<?php

namespace App\Controller\Admin;

use App\Enum\ContentPageType;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;

final class InfrastructurePageCrudController extends AbstractStaticPageCrudController
{
    protected static function pageType(): ContentPageType { return ContentPageType::INFRASTRUCTURE; }
    protected static function pageLabel(): string { return 'Инфраструктура'; }

    public function configureFields(string $pageName): iterable
    {
        yield ArrayField::new('mainTerritoryInfrastructure', 'Инфраструктура основной территории')->setColumns(12);
        yield ArrayField::new('buildingSevenInfrastructure', 'Инфраструктура территории 7 корпуса')->setColumns(12);
    }
}
