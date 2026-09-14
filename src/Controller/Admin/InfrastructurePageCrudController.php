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
        foreach ([
            'mainTerritoryInfrastructure' => 'Инфраструктура основной территории',
            'buildingSevenInfrastructure' => 'Инфраструктура территории 7 корпуса',
        ] as $property => $label) {
            yield ArrayField::new($property, $label)->setColumns(12)
                ->addCssClass('infrastructure-sortable')
                ->addJsFiles('assets/admin/infrastructure-order.js')
                ->addCssFiles('styles/infrastructure-order.css')
                ->setHelp('Перетаскивайте пункты за ↕ или используйте стрелки. Для сохранения порядка нажмите «Сохранить».');
        }
    }
}
