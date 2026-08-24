<?php

namespace App\Controller\Admin;

use App\Enum\SectionSlug;

final class GuestDocumentsCrudController extends AbstractSectionDocumentCrudController
{
    protected static function section(): SectionSlug { return SectionSlug::GUEST_INFO; }
    protected static function label(): string { return 'Информация для гостей — документы'; }
    protected static function hasDescription(): bool { return false; }
    protected static function verticalFields(): bool { return true; }
}
