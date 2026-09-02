<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Traits\SingleUploadTrait;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
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
    public int $modalTimeoutSeconds = 60;

    #[ORM\Column]
    #[Groups('settings:read')]
    public int $slideDurationSeconds = 10;

    #[ORM\Column(length: 2048, nullable: true)]
    #[Groups('settings:read')]
    public ?string $mobileMapUrl = null;

    #[ORM\Column]
    #[Groups('settings:read')]
    public int $maxGeoSnapDistanceMeters = 500;

    /** @var list<string> */
    #[ORM\Column(type: 'json')]
    #[Groups('settings:read')]
    #[Assert\All([new Assert\Type('string')])]
    public array $allowedLinks = [];

    #[ORM\Column(length: 128, nullable: true)]
    #[Groups('settings:read')]
    private ?string $exitPassword = null;

    #[Assert\Length(
        min: 4,
        max: 128,
        minMessage: 'Пароль для выхода должен содержать минимум 4 символа.',
        maxMessage: 'Пароль для выхода не должен быть длиннее 128 символов.',
    )]
    private ?string $plainExitPassword = null;

    public function getId(): ?int { return $this->id; }
    public function __toString(): string { return $this->companyName; }

    public function getPlainExitPassword(): ?string
    {
        return $this->plainExitPassword;
    }

    public function setPlainExitPassword(?string $password): void
    {
        $this->plainExitPassword = $password === '' ? null : $password;
    }

    public function setExitPassword(string $password): void
    {
        $this->exitPassword = $password;
        $this->plainExitPassword = null;
    }

    public function getExitPassword(): ?string
    {
        return $this->exitPassword;
    }

    #[Groups('settings:read')]
    public function isExitPasswordConfigured(): bool
    {
        return $this->exitPassword !== null && $this->exitPassword !== '';
    }
}
