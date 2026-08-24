<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Traits\SingleUploadTrait;
use Symfony\Component\Serializer\Annotation\Groups;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity]
#[Vich\Uploadable]
#[ApiResource(operations: [
    new Get(), new GetCollection(),
    new Get(uriTemplate: '/floors/{id}'),
    new GetCollection(uriTemplate: '/floors', paginationEnabled: false, order: ['title' => 'ASC']),
], normalizationContext: ['groups' => ['map:read']])]
#[ApiFilter(SearchFilter::class, properties: ['territory' => 'exact', 'title' => 'partial'])]
#[ApiFilter(BooleanFilter::class, properties: ['active'])]
class MapPlan
{
    use SingleUploadTrait;
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    #[Groups('map:read')]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups('map:read')]
    public string $title = '';

    #[ORM\Column(length: 64)]
    #[Groups('map:read')]
    public string $territory = 'main';

    #[ORM\Column]
    #[Groups('map:read')]
    public int $width = 0;

    #[ORM\Column]
    #[Groups('map:read')]
    public int $height = 0;

    #[ORM\Column]
    #[Groups('map:read')]
    public bool $active = true;

    public function getId(): ?int { return $this->id; }
    public function __toString(): string { return $this->title; }

    #[Groups('map:read')]
    public function getName(): string { return $this->title; }

    #[Groups('map:read')]
    public function getMapImage(): ?string { return $this->getFileUrl(); }
}
