<?php

namespace App\Controller\Admin;

use App\Enum\SectionSlug;

final class UavAlertDocumentCrudController extends AbstractSectionDocumentCrudController
{
    protected static function section(): SectionSlug { return SectionSlug::UAV_ALERT; }
    protected static function label(): string { return 'Действия при угрозе атаки БПЛА'; }
    protected static function singleton(): bool { return true; }
    protected static function defaultTitle(): string { return 'Памятка о действиях при угрозе атаки БПЛА'; }
}
