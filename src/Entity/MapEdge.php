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
use App\ApiResource\MapRoadInput;
use App\State\MapRoadProcessor;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ApiResource(operations: [
    new Get(), new GetCollection(),
    new Post(input: MapRoadInput::class, processor: MapRoadProcessor::class),
    new Patch(input: MapRoadInput::class, processor: MapRoadProcessor::class),
    new Delete(),
    new Get(uriTemplate: '/roads/{id}'), new GetCollection(uriTemplate: '/roads'),
    new Post(uriTemplate: '/roads', input: MapRoadInput::class, processor: MapRoadProcessor::class),
    new Patch(uriTemplate: '/roads/{id}', input: MapRoadInput::class, processor: MapRoadProcessor::class),
    new Delete(uriTemplate: '/roads/{id}'),
], normalizationContext: ['groups' => ['map:read']], paginationEnabled: false)]
#[ApiFilter(SearchFilter::class, properties: ['plan.id' => 'exact'])]
#[ApiFilter(BooleanFilter::class, properties: ['active', 'accessible', 'bidirectional'])]
class MapEdge
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    #[Groups('map:read')]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups('map:read')]
    public ?MapPlan $plan = null;

    #[ORM\ManyToOne(inversedBy: 'outgoingEdges')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups('map:read')]
    public ?MapNode $fromNode = null;

    #[ORM\ManyToOne(inversedBy: 'incomingEdges')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups('map:read')]
    public ?MapNode $toNode = null;

    #[ORM\Column]
    #[Groups('map:read')]
    public bool $bidirectional = true;

    #[ORM\Column(name: 'is_accessible')]
    #[Groups('map:read')]
    public bool $accessible = true;

    #[ORM\Column(nullable: true)]
    #[Groups('map:read')]
    public ?float $distanceMeters = null;

    #[ORM\Column]
    #[Groups('map:read')]
    public bool $active = true;

    public function getId(): ?int { return $this->id; }
    public function __toString(): string { return sprintf('%s → %s', $this->fromNode, $this->toNode); }
}
