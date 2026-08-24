<?php

namespace App\Controller\Admin;

use App\Enum\ContentPageType;
use App\Controller\Admin\Field\VichFileField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use Symfony\Component\Validator\Constraints\Regex;

final class ResidenceRulesPageCrudController extends AbstractStaticPageCrudController
{
    protected static function pageType(): ContentPageType { return ContentPageType::RESIDENCE_RULES; }
    protected static function pageLabel(): string { return 'Правила проживания'; }
    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('title', 'Заголовок')->setColumns(12);
        yield VichFileField::new('imageFile', 'QR-код полной версии всех правил')->onlyOnForms()->setFormTypeOption('allow_delete', true)->setColumns(12);
        yield TextField::new('checkInTime', 'Время заезда')->setFormTypeOption('constraints', [new Regex('/^(?:[01]\d|2[0-3]):[0-5]\d$/', 'Введите время в формате ЧЧ:ММ.')])->setColumns(12);
        yield TextField::new('checkOutTime', 'Время выезда')->setFormTypeOption('constraints', [new Regex('/^(?:[01]\d|2[0-3]):[0-5]\d$/', 'Введите время в формате ЧЧ:ММ.')])->setColumns(12);
        yield ArrayField::new('placementRules', 'При размещении в санатории...')->setColumns(12);
        yield TextEditorField::new('visitorPassText', 'Пропуск на территорию посетителей')->setColumns(12);
        yield ArrayField::new('safetyRules', 'Для вашей безопасности и комфортного проживания...')->setColumns(12);
        yield ArrayField::new('medicalProcedureRules', 'Правила оказания процедур...')->setColumns(12);
        yield VichFileField::new('logoFile', 'Иконка блока размещения')->onlyOnForms()->setFormTypeOption('allow_delete', true)->setColumns(12);
        yield VichFileField::new('mascotTwoFile', 'Иконка пропуска посетителей')->onlyOnForms()->setFormTypeOption('allow_delete', true)->setColumns(12);
        yield VichFileField::new('extraImageFile', 'Иконка правил процедур')->onlyOnForms()->setFormTypeOption('allow_delete', true)->setColumns(12);
        yield VichFileField::new('fifthImageFile', 'QR-код стоимости возмещения ущерба')->onlyOnForms()->setFormTypeOption('allow_delete', true)->setColumns(12);
    }
}
