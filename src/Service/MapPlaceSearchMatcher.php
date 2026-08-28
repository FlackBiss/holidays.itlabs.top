<?php

namespace App\Service;

use App\Entity\MapPlace;

final readonly class MapPlaceSearchMatcher
{
    public function matches(MapPlace $place, string $query): bool
    {
        $query = trim($query);
        if ($query === '') {
            return false;
        }

        if (mb_stripos($place->name, $query) !== false) {
            return true;
        }

        foreach ($place->searchAliases as $alias) {
            if (is_string($alias) && mb_stripos($alias, $query) !== false) {
                return true;
            }
        }

        return false;
    }
}
