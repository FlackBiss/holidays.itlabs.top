<?php

namespace App\Enum;

enum MapPlaceCategory: string
{
    case OTHER = 'other';
    case SPORT = 'sport';
    case RECREATION = 'recreation';
    case RESIDENTIAL = 'residential';
    case BUILDINGS = 'buildings';

    /** @return array<string, self> */
    public static function choices(): array
    {
        return [
            'Прочее' => self::OTHER,
            'Спорт' => self::SPORT,
            'Места отдыха' => self::RECREATION,
            'Жилые корпуса' => self::RESIDENTIAL,
            'Здания' => self::BUILDINGS,
        ];
    }

    public function label(): string
    {
        return array_search($this, self::choices(), true) ?: $this->value;
    }
}
