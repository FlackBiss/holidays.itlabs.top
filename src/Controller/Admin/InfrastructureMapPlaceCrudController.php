<?php

namespace App\Controller\Admin;

use App\Enum\PlaceType;

final class InfrastructureMapPlaceCrudController extends MapPlaceCrudController
{
    protected static function fixedType(): ?PlaceType { return PlaceType::INFRASTRUCTURE; }
}
