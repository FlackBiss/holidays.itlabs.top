<?php

namespace App\Enum;

enum ContentSection: string
{
    case MEDICAL_DEPARTMENT = 'medical_department';
    case MEDICAL_SERVICE = 'medical_service';
    case CONTACTS = 'contacts';

    public static function choices(): array
    {
        return [
            'Разделы медицинских услуг' => self::MEDICAL_DEPARTMENT,
            'Карточки медицинских услуг' => self::MEDICAL_SERVICE,
            'Контакты' => self::CONTACTS,
        ];
    }

    public function label(): string
    {
        foreach (self::choices() as $label => $section) {
            if ($section === $this) {
                return $label;
            }
        }

        return $this->value;
    }
}
