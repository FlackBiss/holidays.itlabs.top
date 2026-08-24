<?php

namespace App\Enum;

enum MediaType: string
{
    case IMAGE = 'image';
    case VIDEO = 'video';
    case PDF = 'pdf';

    public static function choices(): array
    {
        return ['Изображение' => self::IMAGE, 'Видео' => self::VIDEO, 'PDF' => self::PDF];
    }

    public function label(): string
    {
        return match ($this) {
            self::IMAGE => 'Изображение',
            self::VIDEO => 'Видео',
            self::PDF => 'PDF',
        };
    }
}
