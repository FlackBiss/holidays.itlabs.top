<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use App\ApiResource\MapPlacementInput;
use App\State\TerminalPlacementProcessor;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\UniqueConstraint(name: 'uniq_terminal_code', columns: ['code'])]
#[ApiResource(operations: [
    new Get(), new GetCollection(),
    new Patch(input: MapPlacementInput::class, processor: TerminalPlacementProcessor::class),
    new Patch(uriTemplate: '/kiosk_terminals/{id}/placement', input: MapPlacementInput::class, processor: TerminalPlacementProcessor::class),
    new Get(uriTemplate: '/terminals/{id}'), new GetCollection(uriTemplate: '/terminals'),
    new Patch(uriTemplate: '/terminals/{id}', input: MapPlacementInput::class, processor: TerminalPlacementProcessor::class),
], normalizationContext: ['groups' => ['terminal:read']])]
#[ApiFilter(SearchFilter::class, properties: ['code' => 'exact'])]
class KioskTerminal
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    #[Groups('terminal:read')]
    private ?int $id = null;

    #[ORM\Column(length: 64)]
    #[Groups('terminal:read')]
    public string $code = '';

    #[ORM\Column(length: 255)]
    #[Groups('terminal:read')]
    public string $name = '';

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups('terminal:read')]
    public ?MapNode $startNode = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups('terminal:read')]
    public ?MapArea $area = null;

    #[ORM\Column]
    #[Groups('terminal:read')]
    public bool $active = true;

    #[ORM\Column(nullable: true)]
    #[Groups('terminal:read')]
    public ?\DateTimeImmutable $lastSeenAt = null;

    public function getId(): ?int { return $this->id; }
    #[Groups('terminal:read')]
    public function getStartNodeId(): ?int { return $this->startNode?->getId(); }
    #[Groups('terminal:read')]
    public function getAreaId(): ?int { return $this->area?->getId(); }
    public function __toString(): string { return $this->name ?: $this->code; }
    public function getMapX(): ?float { return $this->startNode?->x; }
    public function setMapX(?float $value): void { if ($value !== null) $this->routeNode()->x = $value; }
    public function getMapY(): ?float { return $this->startNode?->y; }
    public function setMapY(?float $value): void { if ($value !== null) $this->routeNode()->y = $value; }
    public function getLatitude(): ?float { return $this->startNode?->latitude; }
    public function setLatitude(?float $value): void { $this->routeNode()->latitude = $value; }
    public function getLongitude(): ?float { return $this->startNode?->longitude; }
    public function setLongitude(?float $value): void { $this->routeNode()->longitude = $value; }
    private function routeNode(): MapNode { return $this->startNode ??= new MapNode(); }
}
