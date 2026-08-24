<?php
namespace App\Controller\Admin;
use App\Enum\ContentSection;
final class MedicalDepartmentCrudController extends AbstractSectionItemCrudController { protected static function section(): ContentSection { return ContentSection::MEDICAL_DEPARTMENT; } protected static function label(): string { return 'Разделы медицинских услуг'; } }
