<?php

namespace App\Controller\Admin;

use App\Entity\AnimationPoster;

final class AnimationPosterCrudController extends AbstractOrderedMediaCrudController
{
    public static function getEntityFqcn(): string { return AnimationPoster::class; }
    protected static function label(): string { return 'Анимационная программа'; }
    protected static function verticalLayout(): bool { return true; }
    protected static function imageOnly(): bool { return true; }
}
