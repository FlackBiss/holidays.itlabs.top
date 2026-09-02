<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'map_geo_control_point')]
#[ORM\UniqueConstraint(name: 'uniq_geo_control_position', columns: ['calibration_id', 'position'])]
class MapGeoControlPoint
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'controlPoints')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public ?MapGeoCalibration $calibration = null;

    #[ORM\Column]
    public float $x = 0.0;

    #[ORM\Column]
    public float $y = 0.0;

    #[ORM\Column]
    public float $latitude = 0.0;

    #[ORM\Column]
    public float $longitude = 0.0;

    #[ORM\Column]
    public int $position = 0;

    public function getId(): ?int { return $this->id; }
}
