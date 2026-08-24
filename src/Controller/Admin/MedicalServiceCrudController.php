<?php
namespace App\Controller\Admin;
use App\Controller\Admin\Field\VichFileField;
use App\Enum\ContentSection;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;

final class MedicalServiceCrudController extends AbstractSectionItemCrudController
{
    protected static function section(): ContentSection { return ContentSection::MEDICAL_SERVICE; }
    protected static function label(): string { return 'Карточки медицинских услуг'; }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)->setSearchFields(['title'])->setDefaultSort(['priority' => 'ASC', 'title' => 'ASC']);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('title', 'Поиск по названию'))
            ->add(EntityFilter::new('parent', 'Раздел услуг'))
            ->add(BooleanFilter::new('active', 'Показывать'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('parent', 'Раздел услуг')->setQueryBuilder(
            static fn (QueryBuilder $qb): QueryBuilder => $qb->andWhere('entity.section = :department')->andWhere('entity.parent IS NULL')->setParameter('department', ContentSection::MEDICAL_DEPARTMENT),
        )->setRequired(true)->setColumns(12);
        yield TextField::new('title', 'Название')->setColumns(12);
        yield TextEditorField::new('description', 'Описание')->setColumns(12);
        yield VichFileField::new('file', 'Изображение карточки')->onlyOnForms()->setFormTypeOption('allow_delete', true)->setColumns(12);
        yield TextField::new('fileUrl', 'Изображение')->onlyOnIndex();
        yield UrlField::new('url', 'Ссылка')->setColumns(12);
        yield IntegerField::new('priority', 'Порядок отображения')->setColumns(12);
        yield BooleanField::new('active', 'Показывать')->setColumns(12);
    }
}
