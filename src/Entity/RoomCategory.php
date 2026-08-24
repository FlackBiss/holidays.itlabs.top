<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ApiResource(operations: [new Get(), new GetCollection()], normalizationContext: ['groups' => ['map:read']])]
#[ApiFilter(SearchFilter::class, properties: ['places.id' => 'exact', 'title' => 'partial'])]
class RoomCategory
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    #[Groups('map:read')]
    private ?int $id = null;

    /** @var Collection<int, MapPlace> */
    #[ORM\ManyToMany(targetEntity: MapPlace::class, mappedBy: 'roomCategories')]
    public Collection $places;

    #[ORM\Column(length: 255)]
    #[Groups('map:read')]
    public string $title = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups('map:read')]
    public ?string $description = null;

    /** @var Collection<int, RoomCategoryPhoto> */
    #[ORM\OneToMany(targetEntity: RoomCategoryPhoto::class, mappedBy: 'category', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['priority' => 'ASC', 'id' => 'ASC'])]
    #[Groups('map:read')]
    public Collection $photos;

    #[ORM\Column]
    #[Groups('map:read')]
    public int $priority = 0;

    public function __construct() { $this->photos = new ArrayCollection(); $this->places = new ArrayCollection(); }
    public function getId(): ?int { return $this->id; }
    public function __toString(): string { return $this->title; }

    public function addPhoto(RoomCategoryPhoto $photo): void
    {
        if (!$this->photos->contains($photo)) {
            $this->photos->add($photo);
            $photo->category = $this;
        }
    }

    public function removePhoto(RoomCategoryPhoto $photo): void
    {
        $this->photos->removeElement($photo);
    }
}
