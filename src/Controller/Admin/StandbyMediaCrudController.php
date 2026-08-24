<?php
namespace App\Controller\Admin;
use App\Entity\StandbyMedia;
final class StandbyMediaCrudController extends AbstractOrderedMediaCrudController { public static function getEntityFqcn(): string { return StandbyMedia::class; } protected static function label(): string { return 'Режим ожидания'; } }
