<?php

namespace App\Tests\Unit;

use App\Entity\MapPlace;
use App\Service\MapPlaceSearchMatcher;
use PHPUnit\Framework\TestCase;

final class MapPlaceSearchMatcherTest extends TestCase
{
    private MapPlaceSearchMatcher $matcher;

    protected function setUp(): void
    {
        $this->matcher = new MapPlaceSearchMatcher();
    }

    public function testMatchesNameCaseInsensitively(): void
    {
        $place = new MapPlace();
        $place->name = 'Детская площадка';

        self::assertTrue($this->matcher->matches($place, 'ДЕТСКАЯ'));
    }

    public function testMatchesCyrillicAliasCaseInsensitively(): void
    {
        $place = new MapPlace();
        $place->name = 'Бассейн';
        $place->searchAliases = ['Купание', 'Вода', 'Купальник'];

        self::assertTrue($this->matcher->matches($place, 'купание'));
        self::assertTrue($this->matcher->matches($place, 'ВОДА'));
    }

    public function testMatchesPartOfAlias(): void
    {
        $place = new MapPlace();
        $place->name = 'Бассейн';
        $place->searchAliases = ['Купальник'];

        self::assertTrue($this->matcher->matches($place, 'купал'));
    }

    public function testRejectsUnrelatedQuery(): void
    {
        $place = new MapPlace();
        $place->name = 'Бассейн';
        $place->searchAliases = ['Купание', 'Вода'];

        self::assertFalse($this->matcher->matches($place, 'ресторан'));
    }
}
