<?php
namespace App\Controller\Admin;
use App\Entity\MapIcon;
final class MapIconCrudController extends AbstractOrderedMediaCrudController
{
    public static function getEntityFqcn(): string { return MapIcon::class; }
    protected static function label(): string { return 'Иконки карты'; }
    protected static function verticalLayout(): bool { return true; }
}
