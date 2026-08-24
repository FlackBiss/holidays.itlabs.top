<?php

namespace App\Enum;

enum PlaceType: string
{
    case RESIDENTIAL = 'residential';
    case INFRASTRUCTURE = 'infrastructure';

    public static function choices(): array
    {
        return ['Жилой корпус' => self::RESIDENTIAL, 'Инфраструктурный объект' => self::INFRASTRUCTURE];
    }

    public function label(): string
    {
        return match ($this) {
            self::RESIDENTIAL => 'Жилой корпус',
            self::INFRASTRUCTURE => 'Инфраструктурный объект',
        };
    }
}
