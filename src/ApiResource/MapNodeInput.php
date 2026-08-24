<?php

namespace App\ApiResource;

use Symfony\Component\Validator\Constraints as Assert;

final class MapNodeInput
{
    #[Assert\Positive]
    public ?int $planId = null;
    /** Совместимое имя карты из редактора Петровского. */
    #[Assert\Positive]
    public ?int $floor = null;
    /** @var array{x?: float|int, y?: float|int, floor?: int, latitude?: float|int|null, longitude?: float|int|null}|null */
    public ?array $point = null;
    /** @var list<int>|null */
    public ?array $nodes = null;
    /** @var list<int>|null Совместимое поле; доступность хранится на дорогах. */
    public ?array $types = null;
    public ?string $name = null;
    public ?float $x = null;
    public ?float $y = null;
    #[Assert\Range(min: -90, max: 90)]
    public ?float $latitude = null;
    #[Assert\Range(min: -180, max: 180)]
    public ?float $longitude = null;
    public ?bool $active = null;
}
