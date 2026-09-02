<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'map_geo_calibration')]
#[ORM\UniqueConstraint(name: 'uniq_map_geo_calibration_plan', columns: ['plan_id'])]
class MapGeoCalibration
{
    public const string METHOD_PIECEWISE_AFFINE = 'piecewise_affine';

    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'geoCalibration')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public ?MapPlan $plan = null;

    #[ORM\Column(length: 32)]
    public string $method = self::METHOD_PIECEWISE_AFFINE;

    #[ORM\Column]
    public int $version = 1;

    #[ORM\Column]
    public \DateTimeImmutable $createdAt;

    #[ORM\Column]
    public \DateTimeImmutable $updatedAt;

    /** @var Collection<int, MapGeoControlPoint> */
    #[ORM\OneToMany(targetEntity: MapGeoControlPoint::class, mappedBy: 'calibration', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC', 'id' => 'ASC'])]
    private Collection $controlPoints;

    public function __construct()
    {
        $this->controlPoints = new ArrayCollection();
        $this->createdAt = $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    /** @return Collection<int, MapGeoControlPoint> */
    public function getControlPoints(): Collection { return $this->controlPoints; }

    /** @param list<array{x: float, y: float, latitude: float, longitude: float, position: int}> $points */
    public function replaceControlPoints(array $points): void
    {
        $this->controlPoints->clear();
        foreach ($points as $data) {
            $point = new MapGeoControlPoint();
            $point->calibration = $this;
            $point->x = $data['x'];
            $point->y = $data['y'];
            $point->latitude = $data['latitude'];
            $point->longitude = $data['longitude'];
            $point->position = $data['position'];
            $this->controlPoints->add($point);
        }
    }
}
