<?php

namespace App\Controller\Admin;

use App\Entity\KioskTerminal;
use App\Entity\MapEdge;
use App\Entity\MapNode;
use App\Entity\MapPlan;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;

final class KioskTerminalCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string { return KioskTerminal::class; }
    public function configureCrud(Crud $crud): Crud { return $crud->setEntityLabelInPlural('Терминалы')->setEntityLabelInSingular('Терминал'); }
    public function configureFilters(Filters $filters): Filters { return $filters->add(TextFilter::new('code', 'Код'))->add(TextFilter::new('name', 'Название'))->add(BooleanFilter::new('active', 'Активен')); }
    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('code', 'Код терминала')->setHelp('Передаётся фронтом при расчёте маршрута');
        yield TextField::new('name', 'Название');
        yield NumberField::new('mapX', 'Позиция на карте: X, px')->setNumDecimals(2)->setColumns(3)->hideOnIndex();
        yield NumberField::new('mapY', 'Позиция на карте: Y, px')->setNumDecimals(2)->setColumns(3)->hideOnIndex();
        yield NumberField::new('latitude', 'GPS: широта')->setNumDecimals(7)->setColumns(3)->hideOnIndex();
        yield NumberField::new('longitude', 'GPS: долгота')->setNumDecimals(7)->setColumns(3)->hideOnIndex();
        yield BooleanField::new('active', 'Активен');
        yield DateTimeField::new('lastSeenAt', 'Последняя активность')->hideOnForm();
    }
    public function persistEntity(EntityManagerInterface $em, mixed $entity): void { if ($entity instanceof KioskTerminal) $this->prepare($em, $entity); parent::persistEntity($em, $entity); }
    public function updateEntity(EntityManagerInterface $em, mixed $entity): void { if ($entity instanceof KioskTerminal) $this->prepare($em, $entity); parent::updateEntity($em, $entity); }
    private function prepare(EntityManagerInterface $em, KioskTerminal $terminal): void
    {
        $node = $terminal->startNode ??= new MapNode();
        $node->plan ??= $em->getRepository(MapPlan::class)->findOneBy(['active' => true]);
        if (!$node->plan) throw new \LogicException('Сначала настройте карту.');
        $node->name = 'Терминал: '.$terminal->name;
        $node->active = true;
        if ($node->getId()) return;
        $nearest = null; $best = INF;
        foreach ($em->getRepository(MapNode::class)->findBy(['plan' => $node->plan, 'active' => true]) as $candidate) {
            if ($candidate === $node) continue;
            $distance = ($candidate->x - $node->x) ** 2 + ($candidate->y - $node->y) ** 2;
            if ($distance < $best) { $best = $distance; $nearest = $candidate; }
        }
        $em->persist($node);
        if ($nearest) {
            $road = new MapEdge(); $road->plan = $node->plan; $road->fromNode = $nearest; $road->toNode = $node; $road->bidirectional = true; $road->accessible = true;
            $em->persist($road);
        }
    }
}
