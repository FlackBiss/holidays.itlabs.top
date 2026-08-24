<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Traits\SingleUploadTrait;
use Symfony\Component\Serializer\Annotation\Groups;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity]
#[Vich\Uploadable]
#[ORM\UniqueConstraint(name: 'uniq_settings_code', columns: ['code'])]
#[ApiResource(operations: [new Get(), new GetCollection()], normalizationContext: ['groups' => ['settings:read']])]
class SiteSettings
{
    use SingleUploadTrait;
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    #[Groups('settings:read')]
    private ?int $id = null;

    #[ORM\Column(length: 32)]
    public string $code = 'main';

    #[ORM\Column(length: 255)]
    #[Groups('settings:read')]
    public string $companyName = 'Каникулы в Аксаково';

    #[ORM\Column]
    #[Groups('settings:read')]
    public float $latitude = 56.0343584;

    #[ORM\Column]
    #[Groups('settings:read')]
    public float $longitude = 37.6029333;

    #[ORM\Column]
    #[Groups('settings:read')]
    public int $weatherCacheTtl = 900;

    #[ORM\Column]
    #[Groups('settings:read')]
    public int $idleTimeoutSeconds = 120;

    #[ORM\Column]
    #[Groups('settings:read')]
    public int $slideDurationSeconds = 10;

    #[ORM\Column(length: 2048, nullable: true)]
    #[Groups('settings:read')]
    public ?string $mobileMapUrl = null;

    #[ORM\Column]
    #[Groups('settings:read')]
    public int $maxGeoSnapDistanceMeters = 500;

    public function getId(): ?int { return $this->id; }
    public function __toString(): string { return $this->companyName; }
}
