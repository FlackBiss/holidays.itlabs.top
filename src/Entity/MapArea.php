<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\ApiResource\MapAreaInput;
use App\State\MapAreaProcessor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ApiResource(operations: [
    new Get(uriTemplate: '/areas/{id}'),
    new GetCollection(uriTemplate: '/areas', paginationEnabled: false),
    new Post(uriTemplate: '/areas', input: MapAreaInput::class, processor: MapAreaProcessor::class),
    new Patch(uriTemplate: '/areas/{id}', input: MapAreaInput::class, processor: MapAreaProcessor::class),
    new Delete(uriTemplate: '/areas/{id}'),
], normalizationContext: ['groups' => ['map:read']])]
#[ApiFilter(SearchFilter::class, properties: ['plan.id' => 'exact', 'title' => 'partial'])]
#[ApiFilter(BooleanFilter::class, properties: ['active'])]
class MapArea
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    #[Groups('map:read')]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups('map:read')]
    public ?MapPlan $plan = null;

    #[ORM\Column(length: 255)]
    #[Groups('map:read')]
    public string $title = '';

    /** @var Collection<int, MapAreaPoint> */
    #[ORM\OneToMany(targetEntity: MapAreaPoint::class, mappedBy: 'area', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC', 'id' => 'ASC'])]
    #[Groups('map:read')]
    public Collection $points;

    #[ORM\Column]
    #[Groups('map:read')]
    public bool $active = true;

    public function __construct() { $this->points = new ArrayCollection(); }
    public function getId(): ?int { return $this->id; }
    public function __toString(): string { return $this->title ?: 'Область #'.($this->id ?? 'новая'); }

    #[Groups('map:read')]
    public function getFloor(): ?int { return $this->plan?->getId(); }

    #[Groups('map:read')]
    public function getFloorName(): ?string { return $this->plan?->title; }

    public function replacePoints(array $rows): void
    {
        $this->points->clear();
        foreach (array_values($rows) as $position => $row) {
            $point = new MapAreaPoint();
            $point->area = $this;
            $point->x = (float) ($row['x'] ?? 0);
            $point->y = (float) ($row['y'] ?? 0);
            $point->latitude = isset($row['latitude']) ? (float) $row['latitude'] : null;
            $point->longitude = isset($row['longitude']) ? (float) $row['longitude'] : null;
            $point->position = $position;
            $this->points->add($point);
        }
    }
}
