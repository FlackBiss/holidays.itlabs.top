<?php

namespace App\Controller\Admin;

use App\Enum\PlaceType;

final class ResidentialMapPlaceCrudController extends MapPlaceCrudController
{
    protected static function fixedType(): ?PlaceType { return PlaceType::RESIDENTIAL; }
}
