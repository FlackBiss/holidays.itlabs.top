<?php
namespace App\Controller\Admin;
use App\Entity\NewsPoster;
final class NewsPosterCrudController extends AbstractOrderedMediaCrudController
{
    public static function getEntityFqcn(): string { return NewsPoster::class; }
    protected static function label(): string { return 'Новости и акции'; }
    protected static function verticalLayout(): bool { return true; }
    protected static function imageOnly(): bool { return true; }
}
