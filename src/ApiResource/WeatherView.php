<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\WeatherProvider;

#[ApiResource(operations: [new Get(uriTemplate: '/weather/current', provider: WeatherProvider::class)])]
final class WeatherView
{
    public string $location = 'Аксаково';
    public float $latitude;
    public float $longitude;
    public string $observedAt;
    public ?float $temperatureC = null;
    public ?float $feelsLikeC = null;
    public ?float $humidityPercent = null;
    public ?float $windSpeedMps = null;
    public ?float $windDirectionDegrees = null;
    public ?float $precipitationMm = null;
    public ?string $conditionCode = null;
    public string $provider;
    public string $attribution;
    public bool $stale = false;
}
