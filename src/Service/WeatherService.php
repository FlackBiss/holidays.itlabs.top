<?php

namespace App\Service;

use App\ApiResource\WeatherView;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class WeatherService
{
    public function __construct(
        private HttpClientInterface $http,
        private CacheItemPoolInterface $cacheApp,
        private string $provider,
        private string $userAgent,
    ) {}

    public function current(float $latitude, float $longitude, int $ttl): WeatherView
    {
        $lat=round($latitude,4); $lon=round($longitude,4);
        $key='weather_'.md5($this->provider.'_'.$lat.'_'.$lon);
        $item=$this->cacheApp->getItem($key);
        if ($item->isHit()) return $this->hydrate($item->get());
        try {
            $data=$this->provider === 'open_meteo' ? $this->fetchOpenMeteo($lat,$lon) : $this->fetchMetNo($lat,$lon);
            $item->set($data)->expiresAfter(max(60,$ttl)); $this->cacheApp->save($item);
            $fallback=$this->cacheApp->getItem($key.'_last_good'); $fallback->set($data)->expiresAfter(604800); $this->cacheApp->save($fallback);
            return $this->hydrate($data);
        } catch (\Throwable $error) {
            $fallback=$this->cacheApp->getItem($key.'_last_good');
            if (!$fallback->isHit()) throw $error;
            $data=$fallback->get(); $data['stale']=true; return $this->hydrate($data);
        }
    }

    private function fetchMetNo(float $lat, float $lon): array
    {
        $json=$this->http->request('GET','https://api.met.no/weatherapi/locationforecast/2.0/compact',[
            'query'=>['lat'=>$lat,'lon'=>$lon], 'headers'=>['User-Agent'=>$this->userAgent,'Accept'=>'application/json'], 'timeout'=>8,
        ])->toArray();
        $row=$json['properties']['timeseries'][0] ?? throw new \RuntimeException('MET Norway вернул пустой прогноз.');
        $instant=$row['data']['instant']['details'] ?? [];
        $next=$row['data']['next_1_hours'] ?? $row['data']['next_6_hours'] ?? [];
        return [
            'latitude'=>$lat,'longitude'=>$lon,'observedAt'=>$row['time'] ?? (new \DateTimeImmutable())->format(DATE_ATOM),
            'temperatureC'=>$instant['air_temperature'] ?? null,'feelsLikeC'=>null,'humidityPercent'=>$instant['relative_humidity'] ?? null,
            'windSpeedMps'=>$instant['wind_speed'] ?? null,'windDirectionDegrees'=>$instant['wind_from_direction'] ?? null,
            'precipitationMm'=>$next['details']['precipitation_amount'] ?? null,'conditionCode'=>$next['summary']['symbol_code'] ?? null,
            'provider'=>'met_no','attribution'=>'Данные MET Norway','stale'=>false,
        ];
    }

    private function fetchOpenMeteo(float $lat, float $lon): array
    {
        $json=$this->http->request('GET','https://api.open-meteo.com/v1/forecast',[
            'query'=>['latitude'=>$lat,'longitude'=>$lon,'current'=>'temperature_2m,apparent_temperature,relative_humidity_2m,precipitation,weather_code,wind_speed_10m,wind_direction_10m','wind_speed_unit'=>'ms','timezone'=>'Europe/Moscow'], 'timeout'=>8,
        ])->toArray();
        $c=$json['current'] ?? throw new \RuntimeException('Open-Meteo вернул пустой прогноз.');
        return [
            'latitude'=>$lat,'longitude'=>$lon,'observedAt'=>$c['time'] ?? (new \DateTimeImmutable())->format(DATE_ATOM),
            'temperatureC'=>$c['temperature_2m'] ?? null,'feelsLikeC'=>$c['apparent_temperature'] ?? null,'humidityPercent'=>$c['relative_humidity_2m'] ?? null,
            'windSpeedMps'=>$c['wind_speed_10m'] ?? null,'windDirectionDegrees'=>$c['wind_direction_10m'] ?? null,
            'precipitationMm'=>$c['precipitation'] ?? null,'conditionCode'=>isset($c['weather_code']) ? 'wmo_'.$c['weather_code'] : null,
            'provider'=>'open_meteo','attribution'=>'Weather data by Open-Meteo.com','stale'=>false,
        ];
    }

    private function hydrate(array $data): WeatherView
    {
        $view=new WeatherView();
        foreach ($data as $property=>$value) if (property_exists($view,$property)) $view->{$property}=$value;
        return $view;
    }
}
