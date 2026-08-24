<?php

namespace App\ApiResource;

final class NavigationRouteResult
{
    public int $mapId;
    public int $destinationPlaceId;
    public array $points = [];
    public ?array $sourcePosition = null;
    public ?array $snappedPosition = null;
    public float $snapDistanceMeters = 0.0;
    public float $distanceMeters = 0.0;
    public string $mobileUrl;
    public string $qrCodeUrl;
}
