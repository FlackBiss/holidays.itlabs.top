<?php

namespace App\Tests\Unit;

use App\Command\ImportMapInfrastructureCommand;
use App\Enum\MapPlaceCategory;
use PHPUnit\Framework\TestCase;

final class MapInfrastructureImportTest extends TestCase
{
    public function testApprovedInfrastructureCatalogIsCompleteAndOrdered(): void
    {
        $objects = ImportMapInfrastructureCommand::OBJECTS;
        self::assertCount(44, $objects);
        self::assertCount(44, array_unique(array_column($objects, 'name')));
        self::assertCount(44, array_unique(array_column($objects, 'icon')));

        $expectedNames = [
            'sport' => ['Бассейн', 'Тренажерный зал', 'Спортивный зал', 'Боулинг', 'Спортивная площадка', 'Теннисный корт', 'Бадминтон', 'Тренажерный городок', 'Настольный теннис', 'Мини-гольф'],
            'recreation' => ['Обеденный зал', 'Кафе', 'Бар', 'Кинотеатр', 'Игровая комната', 'Сауна', 'Фито-бар', 'Солнечные ванны', 'Беседка для барбекю', 'Мангальная зона', 'Пергола'],
            'other' => ['Парковка', 'Остановка трансфера', 'КПП', 'Охрана', 'Ресепшен', 'Администрация', 'Банкомат', 'Медицина', 'Медпункт', 'Косметология', 'Прачечная Сам Стирай', 'Детская площадка', 'Детская комната', 'Прокат водного инвентаря', 'Место для курения'],
            'buildings' => ['Главный корпус', 'Лечебно-административный корпус', 'Спортивно-оздоровительный корпус', 'Спортбаза (прокат спортинвентаря)', 'Физкультурно-оздоровительный корпус', 'Галерея', 'Часовня Петра и Павла', 'Лодочная станция'],
        ];

        foreach ($expectedNames as $category => $names) {
            self::assertNotNull(MapPlaceCategory::tryFrom($category));
            $categoryObjects = array_values(array_filter($objects, static fn (array $object): bool => $object['category'] === $category));
            self::assertSame($names, array_column($categoryObjects, 'name'));
            self::assertSame(range(1, count($names)), array_column($categoryObjects, 'priority'));
        }
    }
}
