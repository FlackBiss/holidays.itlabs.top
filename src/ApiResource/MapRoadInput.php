<?php

namespace App\ApiResource;

use Symfony\Component\Validator\Constraints as Assert;

final class MapRoadInput
{
    #[Assert\Positive]
    public ?int $planId = null;
    #[Assert\Positive]
    public ?int $fromNodeId = null;
    #[Assert\Positive]
    public ?int $toNodeId = null;
    public ?bool $bidirectional = null;
    public ?bool $accessible = null;
    #[Assert\PositiveOrZero]
    public ?float $distanceMeters = null;
    public ?bool $active = null;
}
