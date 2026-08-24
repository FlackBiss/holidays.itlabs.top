<?php

namespace App\ApiResource;

use Symfony\Component\Validator\Constraints as Assert;

final class MapPlacementInput
{
    #[Assert\Positive]
    public ?int $nodeId = null;
    #[Assert\Positive]
    public ?int $areaId = null;
    /** Совместимые имена полей Петровского. */
    #[Assert\Positive]
    public ?int $node = null;
    #[Assert\Positive]
    public ?int $area = null;
}
