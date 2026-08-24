<?php

namespace App\Controller\Admin;

use App\Enum\SectionSlug;

final class PriceDocumentsCrudController extends AbstractSectionDocumentCrudController
{
    protected static function section(): SectionSlug { return SectionSlug::PRICES; }
    protected static function label(): string { return 'Стоимость услуг — прайсы'; }
    protected static function hasDescription(): bool { return false; }
    protected static function verticalFields(): bool { return true; }
}
