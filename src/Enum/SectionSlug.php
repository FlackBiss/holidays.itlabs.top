<?php

namespace App\Enum;

enum SectionSlug: string
{
    case ABOUT = 'about';
    case GUEST_INFO = 'guest-info';
    case SERVICE_HOURS = 'service-hours';
    case MEAL_TIMES = 'meal-times';
    case CONNECT = 'connect';
    case TRANSFER = 'transfer';
    case PUBLIC_TRANSPORT = 'public-transport';
    case NEWS = 'news';
    case ANIMATION = 'animation';
    case INFRASTRUCTURE = 'infrastructure';
    case MEDICAL_CENTER = 'medical-center';
    case GALLERY = 'gallery';
    case PRICES = 'prices';
    case RESIDENCE_RULES = 'residence-rules';
    case UAV_ALERT = 'uav-alert';
    case TAGANROG = 'taganrog';

    /** @return array<string, self> */
    public static function choices(): array
    {
        $choices = [];
        foreach (self::cases() as $case) {
            $choices[$case->label()] = $case;
        }

        return $choices;
    }

    public function label(): string
    {
        return match ($this) {
            self::ABOUT => 'О санатории',
            self::GUEST_INFO => 'Информация для гостей',
            self::SERVICE_HOURS => 'Контакты',
            self::MEAL_TIMES => 'Время питания',
            self::CONNECT => 'Программа «Подключайся»',
            self::TRANSFER => 'Расписание трансфера',
            self::PUBLIC_TRANSPORT => 'Общественный транспорт',
            self::NEWS => 'Новости и акции',
            self::ANIMATION => 'Анимационная программа',
            self::INFRASTRUCTURE => 'Инфраструктура',
            self::MEDICAL_CENTER => 'Медицинский центр',
            self::GALLERY => 'Фотогалерея',
            self::PRICES => 'Стоимость услуг',
            self::RESIDENCE_RULES => 'Правила проживания',
            self::UAV_ALERT => 'Действия при угрозе атаки БПЛА',
            self::TAGANROG => 'Каникулы в Таганроге',
        };
    }
}
