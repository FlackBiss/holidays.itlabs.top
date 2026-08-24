<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
class MapAreaPoint
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    #[Groups('map:read')]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'points')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public ?MapArea $area = null;

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
    public int $position = 0;

    public function getId(): ?int { return $this->id; }

    #[Groups('map:read')]
    public function getFloor(): ?int { return $this->area?->plan?->getId(); }
}
