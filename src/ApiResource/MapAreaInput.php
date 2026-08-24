<?php

namespace App\ApiResource;

use Symfony\Component\Validator\Constraints as Assert;

final class MapAreaInput
{
    #[Assert\Positive]
    public ?int $planId = null;
    /** Совместимое имя карты из редактора Петровского. */
    #[Assert\Positive]
    public ?int $floor = null;
    public ?string $title = null;
    /** @var list<array{x: float|int, y: float|int, latitude?: float|int|null, longitude?: float|int|null}>|null */
    #[Assert\Count(min: 3)]
    public ?array $points = null;
    public ?bool $active = null;
}
