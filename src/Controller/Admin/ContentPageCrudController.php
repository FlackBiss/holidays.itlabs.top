<?php

namespace App\Controller\Admin;

use App\Controller\Admin\Field\VichFileField;
use App\Entity\ContentPage;
use App\Enum\ContentPageType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class ContentPageCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string { return ContentPage::class; }
    public function configureCrud(Crud $crud): Crud { return $crud->setEntityLabelInPlural('Статичные разделы')->setEntityLabelInSingular('раздел'); }
    public function configureFields(string $pageName): iterable
    {
        yield ChoiceField::new('type','Раздел')->setChoices(ContentPageType::choices())->setColumns(8);
        yield TextField::new('title','Заголовок')->setColumns(8);
        yield TextEditorField::new('description','Описание')->setColumns(8);
        yield VichFileField::new('imageFile','Основное изображение')->onlyOnForms()->setFormTypeOption('allow_delete', true)->setColumns(8);
        yield VichFileField::new('logoFile','Логотип / маскот')->onlyOnForms()->setFormTypeOption('allow_delete', true)->setColumns(8);
        yield ArrayField::new('data','Списки и дополнительные значения')->setColumns(8)->hideOnIndex();
        yield BooleanField::new('active','Показывать');
    }
}
