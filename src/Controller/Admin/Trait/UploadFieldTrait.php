<?php

namespace App\Controller\Admin\Trait;

use App\Controller\Admin\Field\VichFileField;
use App\Controller\Admin\Field\VichImageField;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;

trait UploadFieldTrait
{
    public function getUploadField(
        string $propertyName,
        string $label,
        int $columns,
        ?array $extensions = null,
        UploadFieldType $type = UploadFieldType::FILE,
        bool $required = false
    ): FieldInterface {
        $fieldClass = $type === UploadFieldType::IMAGE
            ? VichImageField::class
            : VichFileField::class;

        $help = $this->formatHelpBadges($extensions ?? $this->getDefaultExtensions($type));

        return $fieldClass::new($propertyName, $label)
            ->setHelp($help)
            ->onlyOnForms()
            ->setFormTypeOption('allow_delete', false)
            ->setRequired($required)
            ->setColumns($columns);
    }

    private function getDefaultExtensions(UploadFieldType $type): array
    {
        return match ($type) {
            UploadFieldType::IMAGE => ['jpg', 'jpeg', 'png', 'jiff', 'webp'],
            UploadFieldType::FILE => ['webp', 'mp4'],
        };
    }

    private function formatHelpBadges(array $extensions): string
    {
        if (empty($extensions)) {
            return '';
        }

        return sprintf(
            '<div class="mt-3">%s</div>',
            implode('', array_map(
                fn($ext) => sprintf('<span class="badge badge-info">*.%s</span>', htmlspecialchars($ext)),
                $extensions
            ))
        );
    }
}
