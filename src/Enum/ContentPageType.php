<?php

namespace App\Enum;

enum ContentPageType: string
{
    case ABOUT = 'about';
    case GUEST_INFO = 'guest_info';
    case SERVICE_HOURS = 'service_hours';
    case CONNECT = 'connect';
    case RESIDENCE_RULES = 'residence_rules';
    case TAGANROG = 'taganrog';
    case MEDICAL_CENTER = 'medical_center';
    case MEAL_TIMES = 'meal_times';
    case INFRASTRUCTURE = 'infrastructure';
    case TRANSFER = 'transfer';

    public static function choices(): array
    {
        return [
            'О санатории' => self::ABOUT,
            'Информация для гостей' => self::GUEST_INFO,
            'Контакты' => self::SERVICE_HOURS,
            'Программа «Подключайся»' => self::CONNECT,
            'Правила проживания' => self::RESIDENCE_RULES,
            'Каникулы в Таганроге' => self::TAGANROG,
            'Медицинский центр' => self::MEDICAL_CENTER,
            'Время питания' => self::MEAL_TIMES,
            'Инфраструктура' => self::INFRASTRUCTURE,
            'Трансфер между территориями' => self::TRANSFER,
        ];
    }
}
