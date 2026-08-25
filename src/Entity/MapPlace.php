<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use App\ApiResource\MapPlacementInput;
use App\Enum\MapPlaceCategory;
use App\Enum\PlaceType;
use App\State\MapPlacePlacementProcessor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity]
#[Vich\Uploadable]
#[ApiResource(operations: [
    new Get(),
    new GetCollection(order: ['priority' => 'ASC', 'name' => 'ASC']),
    new Patch(input: MapPlacementInput::class, processor: MapPlacePlacementProcessor::class),
    new Patch(uriTemplate: '/map_places/{id}/placement', input: MapPlacementInput::class, processor: MapPlacePlacementProcessor::class),
    new Get(uriTemplate: '/objects/{id}'),
    new GetCollection(uriTemplate: '/objects', order: ['priority' => 'ASC', 'name' => 'ASC']),
    new Patch(uriTemplate: '/objects/{id}', input: MapPlacementInput::class, processor: MapPlacePlacementProcessor::class),
], normalizationContext: ['groups' => ['map:read']], paginationEnabled: false)]
#[ApiFilter(SearchFilter::class, properties: ['plan.id' => 'exact', 'type' => 'exact', 'category' => 'exact', 'name' => 'partial', 'searchAliases' => 'partial'])]
#[ApiFilter(BooleanFilter::class, properties: ['active', 'onlineBooking', 'routeDrawn'])]
#[ApiFilter(OrderFilter::class, properties: ['priority', 'name'])]
class MapPlace
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    #[Groups('map:read')]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups('map:read')]
    public ?MapPlan $plan = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups('map:read')]
    public ?MapNode $node = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups('map:read')]
    public ?MapArea $area = null;

    #[ORM\Column(enumType: PlaceType::class)]
    #[Groups('map:read')]
    public PlaceType $type = PlaceType::INFRASTRUCTURE;

    #[ORM\Column(enumType: MapPlaceCategory::class)]
    #[Groups('map:read')]
    public MapPlaceCategory $category = MapPlaceCategory::OTHER;

    #[ORM\Column(length: 255)]
    #[Groups('map:read')]
    public string $name = '';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $iconFileName = null;

    #[Vich\UploadableField(mapping: 'map_place_icons', fileNameProperty: 'iconFileName')]
    #[Assert\File(maxSize: '10M', mimeTypes: ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'])]
    private ?File $iconFile = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $iconUpdatedAt = null;

    #[Groups('map:read')]
    public function getCoverUrl(): ?string
    {
        $first = $this->photos->first();
        return $first instanceof MapPlacePhoto ? $first->getUrl() : null;
    }

    /** @var Collection<int, MapPlacePhoto> */
    #[ORM\OneToMany(targetEntity: MapPlacePhoto::class, mappedBy: 'place', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['priority' => 'ASC', 'id' => 'ASC'])]
    #[Groups('map:read')]
    public Collection $photos;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups('map:read')]
    public ?string $description = null;

    #[ORM\Column(length: 64, nullable: true)]
    #[Groups('map:read')]
    public ?string $buildingNumber = null;

    #[ORM\Column(nullable: true)]
    #[Groups('map:read')]
    public ?int $floorCount = null;

    #[ORM\Column(nullable: true)]
    #[Groups('map:read')]
    public ?int $roomCount = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups('map:read')]
    public ?string $workingHours = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups('map:read')]
    public ?string $phone = null;

    #[ORM\Column]
    #[Groups('map:read')]
    public bool $onlineBooking = false;

    #[ORM\Column]
    #[Groups('map:read')]
    public bool $routeDrawn = false;

    #[ORM\Column(length: 2048, nullable: true)]
    #[Groups('map:read')]
    public ?string $bookingUrl = null;

    #[ORM\Column(type: Types::JSON)]
    #[Groups('map:read')]
    public array $searchAliases = [];

    #[ORM\Column]
    #[Groups('map:read')]
    public int $priority = 0;

    #[ORM\Column]
    #[Groups('map:read')]
    public bool $active = true;

    /** @var Collection<int, RoomCategory> */
    #[ORM\ManyToMany(targetEntity: RoomCategory::class, inversedBy: 'places')]
    #[ORM\JoinTable(name: 'map_place_room_category')]
    #[ORM\OrderBy(['priority' => 'ASC', 'id' => 'ASC'])]
    #[Groups('map:read')]
    public Collection $roomCategories;

    public function __construct() { $this->photos = new ArrayCollection(); $this->roomCategories = new ArrayCollection(); }
    public function getId(): ?int { return $this->id; }
    public function __toString(): string { return $this->name; }

    public function getIconFile(): ?File { return $this->iconFile; }
    public function getIconFileName(): ?string { return $this->iconFileName; }
    public function setIconFileName(?string $fileName): void { $this->iconFileName = $fileName; }

    public function setIconFile(?File $file): void
    {
        $this->iconFile = $file;
        if ($file) $this->iconUpdatedAt = new \DateTimeImmutable();
    }

    #[Groups('map:read')]
    public function getIcon(): ?array
    {
        $url = $this->getIconUrl();
        return $url ? ['url' => $url] : null;
    }

    public function getIconUrl(): ?string
    {
        return $this->iconFileName ? '/uploads/map-place-icons/'.$this->iconFileName : null;
    }

    #[Groups('map:read')]
    public function getCategoryLabel(): string { return $this->category->label(); }

    #[Groups('map:read')]
    public function isRouteAvailable(): bool { return $this->type !== PlaceType::INFRASTRUCTURE || $this->routeDrawn; }

    public function addPhoto(MapPlacePhoto $photo): void
    {
        if (!$this->photos->contains($photo)) {
            $this->photos->add($photo);
            $photo->place = $this;
        }
    }

    public function removePhoto(MapPlacePhoto $photo): void
    {
        $this->photos->removeElement($photo);
    }

    public function addRoomCategory(RoomCategory $category): void
    {
        if (!$this->roomCategories->contains($category)) {
            $this->roomCategories->add($category);
            if (!$category->places->contains($this)) $category->places->add($this);
        }
    }

    public function removeRoomCategory(RoomCategory $category): void
    {
        $this->roomCategories->removeElement($category);
        $category->places->removeElement($this);
    }

    public function getMapX(): ?float { return $this->node?->x; }
    public function setMapX(?float $value): void { if ($value !== null) $this->routeNode()->x = $value; }
    public function getMapY(): ?float { return $this->node?->y; }
    public function setMapY(?float $value): void { if ($value !== null) $this->routeNode()->y = $value; }
    public function getLatitude(): ?float { return $this->node?->latitude; }
    public function setLatitude(?float $value): void { $this->routeNode()->latitude = $value; }
    public function getLongitude(): ?float { return $this->node?->longitude; }
    public function setLongitude(?float $value): void { $this->routeNode()->longitude = $value; }

    private function routeNode(): MapNode
    {
        return $this->node ??= new MapNode();
    }

    #[Assert\Callback]
    public function validatePlaceType(ExecutionContextInterface $context): void
    {
        if ($this->type === PlaceType::RESIDENTIAL) return;
        if ($this->buildingNumber !== null || $this->floorCount !== null || $this->roomCount !== null) {
            $context->buildViolation('Номер корпуса, этажность и количество номеров допустимы только для жилых корпусов.')->atPath('type')->addViolation();
        }
    }
}
