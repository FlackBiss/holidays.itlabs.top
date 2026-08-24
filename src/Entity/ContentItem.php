<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Enum\ContentSection;
use App\Entity\Traits\SingleUploadTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity]
#[Vich\Uploadable]
#[ApiResource(operations: [new Get(), new GetCollection(order: ['priority' => 'ASC', 'id' => 'ASC'])], normalizationContext: ['groups' => ['content:read']], paginationEnabled: false)]
#[ApiFilter(SearchFilter::class, properties: ['section' => 'exact', 'territory' => 'exact', 'parent.id' => 'exact', 'title' => 'partial'])]
#[ApiFilter(BooleanFilter::class, properties: ['active', 'onlineBooking'])]
#[ApiFilter(OrderFilter::class, properties: ['priority', 'title', 'id'])]
class ContentItem
{
    use SingleUploadTrait;
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    #[Groups('content:read')]
    private ?int $id = null;

    #[ORM\Column(enumType: ContentSection::class)]
    #[Groups('content:read')]
    public ContentSection $section = ContentSection::CONTACTS;

    #[ORM\ManyToOne(targetEntity: ContentItem::class)]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    public ?self $parent = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups('content:read')]
    public ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups('content:read')]
    public ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups('content:read')]
    public ?string $territory = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups('content:read')]
    public ?string $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups('content:read')]
    public ?string $workingDays = null;

    #[ORM\Column(length: 5, nullable: true)]
    #[Groups('content:read')]
    public ?string $startsAt = null;

    #[ORM\Column(length: 5, nullable: true)]
    #[Groups('content:read')]
    public ?string $endsAt = null;

    #[ORM\Column(length: 5, nullable: true)]
    #[Groups('content:read')]
    public ?string $breakStartsAt = null;

    #[ORM\Column(length: 5, nullable: true)]
    #[Groups('content:read')]
    public ?string $breakEndsAt = null;

    #[ORM\Column]
    #[Groups('content:read')]
    public bool $roundTheClock = false;

    #[ORM\Column(type: Types::JSON)]
    #[Groups('content:read')]
    public array $times = [];

    #[ORM\Column(type: Types::JSON)]
    #[Groups('content:read')]
    public array $weekdaysTimes = [];

    #[ORM\Column(type: Types::JSON)]
    #[Groups('content:read')]
    public array $weekendsTimes = [];

    #[ORM\Column(length: 2048, nullable: true)]
    #[Groups('content:read')]
    public ?string $url = null;

    #[ORM\Column]
    #[Groups('content:read')]
    public bool $onlineBooking = false;

    #[ORM\Column(nullable: true)]
    #[Groups('content:read')]
    public ?int $points = null;

    #[ORM\Column]
    #[Groups('content:read')]
    public int $priority = 0;

    #[ORM\Column(type: Types::JSON)]
    #[Groups('content:read')]
    public array $data = [];

    #[ORM\Column]
    #[Groups('content:read')]
    public bool $active = true;

    public function getId(): ?int { return $this->id; }
    #[Groups('content:read')]
    public function getParentId(): ?int { return $this->parent?->getId(); }
    public function __toString(): string { return $this->title ?: $this->section->value; }
}
