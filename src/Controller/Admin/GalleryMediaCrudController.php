<?php
namespace App\Controller\Admin;
use App\Entity\GalleryMedia;
final class GalleryMediaCrudController extends AbstractOrderedMediaCrudController { public static function getEntityFqcn(): string { return GalleryMedia::class; } protected static function label(): string { return 'Фотогалерея'; } protected static function verticalLayout(): bool { return true; } }
