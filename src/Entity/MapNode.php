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
use App\ApiResource\MapNodeInput;
use App\State\MapNodeProcessor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ApiResource(operations: [
    new Get(), new GetCollection(),
    new Post(input: MapNodeInput::class, processor: MapNodeProcessor::class),
    new Patch(input: MapNodeInput::class, processor: MapNodeProcessor::class),
    new Delete(),
    new Get(uriTemplate: '/points/{id}'), new GetCollection(uriTemplate: '/points'),
    new Post(uriTemplate: '/points', input: MapNodeInput::class, processor: MapNodeProcessor::class),
    new Patch(uriTemplate: '/points/{id}', input: MapNodeInput::class, processor: MapNodeProcessor::class),
    new Delete(uriTemplate: '/points/{id}'),
    new Get(uriTemplate: '/nodes/{id}'), new GetCollection(uriTemplate: '/nodes'),
    new Post(uriTemplate: '/nodes', input: MapNodeInput::class, processor: MapNodeProcessor::class),
    new Patch(uriTemplate: '/nodes/{id}', input: MapNodeInput::class, processor: MapNodeProcessor::class),
    new Delete(uriTemplate: '/nodes/{id}'),
], normalizationContext: ['groups' => ['map:read']], paginationEnabled: false)]
#[ApiFilter(SearchFilter::class, properties: ['plan.id' => 'exact', 'name' => 'partial'])]
#[ApiFilter(BooleanFilter::class, properties: ['active'])]
class MapNode
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    #[Groups('map:read')]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups('map:read')]
    public ?MapPlan $plan = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups('map:read')]
    public ?string $name = null;

    #[ORM\Column]
    #[Groups('map:read')]
    public float $x = 0.0;

    #[ORM\Column]
    #[Groups('map:read')]
    public float $y = 0.0;

    #[ORM\Column(nullable: true)]
    #[Groups('map:read')]
    public ?float $latitude = null;

    #[ORM\Column(nullable: true)]
    #[Groups('map:read')]
    public ?float $longitude = null;

    #[ORM\Column]
    #[Groups('map:read')]
    public bool $active = true;

    /** @var Collection<int, MapEdge> */
    #[ORM\OneToMany(targetEntity: MapEdge::class, mappedBy: 'fromNode')]
    private Collection $outgoingEdges;

    /** @var Collection<int, MapEdge> */
    #[ORM\OneToMany(targetEntity: MapEdge::class, mappedBy: 'toNode')]
    private Collection $incomingEdges;

    public function __construct()
    {
        $this->outgoingEdges = new ArrayCollection();
        $this->incomingEdges = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function __toString(): string { return $this->name ?: 'Узел #'.($this->id ?? 'new'); }

    public function addOutgoingEdge(MapEdge $edge): void
    {
        if (!$this->outgoingEdges->contains($edge)) $this->outgoingEdges->add($edge);
    }

    public function removeOutgoingEdge(MapEdge $edge): void
    {
        $this->outgoingEdges->removeElement($edge);
    }

    #[Groups('map:read')]
    public function getFloor(): ?int { return $this->plan?->getId(); }

    /** @return array{id: int|null, x: float, y: float, floor: int|null, latitude: float|null, longitude: float|null} */
    #[Groups('map:read')]
    public function getPoint(): array
    {
        return ['id' => $this->id, 'x' => $this->x, 'y' => $this->y, 'floor' => $this->getFloor(), 'latitude' => $this->latitude, 'longitude' => $this->longitude];
    }

    /** @return list<int> */
    #[Groups('map:read')]
    public function getNodes(): array
    {
        $ids = [];
        foreach ($this->outgoingEdges as $edge) if ($edge->active && $edge->toNode?->getId()) $ids[] = $edge->toNode->getId();
        foreach ($this->incomingEdges as $edge) if ($edge->active && $edge->bidirectional && $edge->fromNode?->getId()) $ids[] = $edge->fromNode->getId();
        return array_values(array_unique($ids));
    }

    /** @return list<int> */
    #[Groups('map:read')]
    public function getTypes(): array { return [1, 2]; }
}
