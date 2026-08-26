<?php

namespace App\Controller\Admin;

use App\Controller\Admin\Field\VichFileField;
use App\Entity\MapEdge;
use App\Entity\MapNode;
use App\Entity\MapPlace;
use App\Entity\MapPlan;
use App\Enum\MapPlaceCategory;
use App\Enum\PlaceType;
use App\Form\MapPlacePhotoType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;

class MapPlaceCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string { return MapPlace::class; }
    protected static function fixedType(): ?PlaceType { return null; }

    public function configureCrud(Crud $crud): Crud
    {
        $plural = match (static::fixedType()) {
            PlaceType::RESIDENTIAL => 'Жилые корпуса',
            PlaceType::INFRASTRUCTURE => 'Инфраструктурные объекты',
            null => 'Все объекты карты',
        };
        $singular = match (static::fixedType()) {
            PlaceType::RESIDENTIAL => 'Жилой корпус',
            PlaceType::INFRASTRUCTURE => 'Инфраструктурный объект',
            null => 'Объект карты',
        };
        return $crud->setEntityLabelInPlural($plural)->setEntityLabelInSingular($singular)->setDefaultSort(['priority' => 'ASC', 'name' => 'ASC']);
    }

    public function configureAssets(Assets $assets): Assets
    {
        return $assets->addJsFile('assets/admin/map-place-form.js');
    }

    public function configureFilters(Filters $filters): Filters
    {
        $filters->add(TextFilter::new('name', 'Название'))->add(BooleanFilter::new('active', 'Показывать'));
        if (static::fixedType() === null) $filters->add(ChoiceFilter::new('type', 'Тип объекта')->setChoices(PlaceType::choices()));
        $filters->add(ChoiceFilter::new('category', 'Категория легенды')->setChoices(MapPlaceCategory::choices()));
        if (static::fixedType() !== PlaceType::RESIDENTIAL) $filters->add(BooleanFilter::new('onlineBooking', 'Возможна онлайн-запись'));
        return $filters;
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addTab('Основное')->setIcon('fa fa-circle-info')->onlyOnForms();

        if (static::fixedType() === null) {
            yield ChoiceField::new('type', 'Тип объекта')
                ->setChoices(PlaceType::choices())
                ->setFormTypeOption('choice_value', static fn (?PlaceType $choice): string => $choice?->value ?? '')
                ->formatValue(static fn (mixed $value, ?MapPlace $place): string => $place?->type->label() ?? '')
                ->setColumns(12);
        }

        yield TextField::new('name', 'Название')->setColumns(12);
        yield TextEditorField::new('description', 'Короткое описание')->hideOnIndex()->setColumns(12);
        yield VichFileField::new('iconFile', 'Иконка для карты')
            ->onlyOnForms()
            ->setFormTypeOption('allow_delete', true)
            ->setHelp('PNG, JPG, WEBP или SVG. Иконка загружается прямо для этого объекта.')
            ->setColumns(12);
        yield ImageField::new('iconUrl', 'Иконка')->onlyOnIndex();
        yield ChoiceField::new('category', 'Категория легенды')->setChoices(MapPlaceCategory::choices())->setColumns(12);
        yield ArrayField::new('searchAliases', 'Дополнительные слова для поиска')->hideOnIndex()->setColumns(12);
        yield IntegerField::new('priority', 'Порядок отображения')->setColumns(12);
        yield BooleanField::new('active', 'Показывать')->setColumns(12);

        yield FormField::addTab('Фотографии')->setIcon('fa fa-images')->onlyOnForms();
        yield CollectionField::new('photos', 'Фотографии слайдера')
            ->setEntryType(MapPlacePhotoType::class)
            ->allowAdd()->allowDelete()
            ->setFormTypeOption('by_reference', false)
            ->setHelp('Первое по порядку фото используется как обложка карточки.')
            ->onlyOnForms()->setColumns(12);

        yield FormField::addTab('Положение на карте')->setIcon('fa fa-location-dot')->onlyOnForms();
        yield NumberField::new('mapX', 'Позиция на карте: X, px')->setNumDecimals(2)->setFormTypeOption('attr', ['readonly' => true])->setColumns(12)->hideOnIndex();
        yield NumberField::new('mapY', 'Позиция на карте: Y, px')->setNumDecimals(2)->setFormTypeOption('attr', ['readonly' => true])->setColumns(12)->hideOnIndex();
        yield NumberField::new('latitude', 'GPS: широта')->setNumDecimals(7)->setColumns(12)->hideOnIndex();
        yield NumberField::new('longitude', 'GPS: долгота')->setNumDecimals(7)->setColumns(12)->hideOnIndex();

        if (static::fixedType() !== PlaceType::INFRASTRUCTURE) {
            yield FormField::addTab('Жилой корпус')->setIcon('fa fa-building')->addCssClass('place-tab-residential')->onlyOnForms();
            yield TextField::new('buildingNumber', 'Номер корпуса')->addCssClass('place-field-residential')->setColumns(12);
            yield IntegerField::new('floorCount', 'Количество этажей')->addCssClass('place-field-residential')->setColumns(12);
            yield IntegerField::new('roomCount', 'Количество номеров')->addCssClass('place-field-residential')->setColumns(12);
            yield AssociationField::new('roomCategories', 'Категории номеров этого корпуса')
                ->autocomplete()
                ->setFormTypeOption('by_reference', false)
                ->setHelp('Категории создаются отдельно в разделе «Категории номеров», здесь выберите нужные для корпуса.')
                ->addCssClass('place-field-residential')
                ->onlyOnForms()->setColumns(12);
        }
        if (static::fixedType() !== PlaceType::RESIDENTIAL) {
            yield FormField::addTab('Инфраструктурный объект')->setIcon('fa fa-tree-city')->addCssClass('place-tab-infrastructure')->onlyOnForms();
            yield TextEditorField::new('workingHours', 'Время работы')->addCssClass('place-field-infrastructure')->setColumns(12);
            yield TextField::new('phone', 'Телефон')->addCssClass('place-field-infrastructure')->setColumns(12);
            yield BooleanField::new('onlineBooking', 'Возможна онлайн-запись')->addCssClass('place-field-infrastructure')->setColumns(12);
            yield BooleanField::new('routeDrawn', 'Маршрут расчерчен')->addCssClass('place-field-infrastructure')->setColumns(12);
            yield UrlField::new('bookingUrl', 'Ссылка на онлайн-запись')->hideOnIndex()->addCssClass('place-field-infrastructure')->setColumns(12);
        }
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $query = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        if (static::fixedType()) $query->andWhere('entity.type = :fixedPlaceType')->setParameter('fixedPlaceType', static::fixedType());
        return $query;
    }

    public function persistEntity(EntityManagerInterface $entityManager, mixed $entityInstance): void
    {
        if ($entityInstance instanceof MapPlace) $this->preparePlace($entityManager, $entityInstance);
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, mixed $entityInstance): void
    {
        if ($entityInstance instanceof MapPlace) $this->preparePlace($entityManager, $entityInstance);
        parent::updateEntity($entityManager, $entityInstance);
    }

    private function preparePlace(EntityManagerInterface $em, MapPlace $place): void
    {
        if (static::fixedType()) $place->type = static::fixedType();
        $place->plan ??= $em->getRepository(MapPlan::class)->findOneBy(['active' => true]);
        if (!$place->plan) throw new \LogicException('Сначала необходимо настроить карту.');

        if ($place->type === PlaceType::RESIDENTIAL) {
            $place->workingHours = null; $place->phone = null; $place->onlineBooking = false; $place->bookingUrl = null;
        } else {
            $place->buildingNumber = null; $place->floorCount = null; $place->roomCount = null; $place->roomCategories->clear();
            if (!$place->onlineBooking) $place->bookingUrl = null;
        }

        $node = $place->node ??= new MapNode();
        $node->plan = $place->plan; $node->name = $place->name; $node->active = true;
        if (!$node->getId()) {
            $nearest = $this->nearestNode($em, $place->plan, $node);
            $em->persist($node);
            if ($nearest) {
                $edge = new MapEdge(); $edge->plan = $place->plan; $edge->fromNode = $nearest; $edge->toNode = $node;
                $edge->bidirectional = true; $edge->accessible = true; $em->persist($edge);
            }
        }
    }

    private function nearestNode(EntityManagerInterface $em, MapPlan $plan, MapNode $target): ?MapNode
    {
        $nearest = null; $bestDistance = INF;
        foreach ($em->getRepository(MapNode::class)->findBy(['plan' => $plan, 'active' => true]) as $node) {
            if ($node === $target) continue;
            $distance = ($node->x - $target->x) ** 2 + ($node->y - $target->y) ** 2;
            if ($distance < $bestDistance) { $bestDistance = $distance; $nearest = $node; }
        }
        return $nearest;
    }
}
