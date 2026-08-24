<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Enum\ContentPageType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity]
#[Vich\Uploadable]
#[ORM\UniqueConstraint(name: 'uniq_content_page_type', columns: ['type'])]
#[ApiResource(operations: [new Get(), new GetCollection()], normalizationContext: ['groups' => ['content:read']])]
#[ApiFilter(SearchFilter::class, properties: ['type' => 'exact', 'active' => 'exact'])]
class ContentPage
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    #[Groups('content:read')]
    private ?int $id = null;

    #[ORM\Column(enumType: ContentPageType::class)]
    #[Groups('content:read')]
    public ContentPageType $type = ContentPageType::ABOUT;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups('content:read')]
    public ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups('content:read')]
    public ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageFileName = null;

    #[Vich\UploadableField(mapping: 'content_files', fileNameProperty: 'imageFileName')]
    #[Assert\File(maxSize: '100M', mimeTypes: ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'])]
    private ?File $imageFile = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $imageUpdatedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logoFileName = null;

    #[Vich\UploadableField(mapping: 'content_files', fileNameProperty: 'logoFileName')]
    #[Assert\File(maxSize: '25M', mimeTypes: ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'])]
    private ?File $logoFile = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $logoUpdatedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $mascotTwoFileName = null;

    #[Vich\UploadableField(mapping: 'content_files', fileNameProperty: 'mascotTwoFileName')]
    #[Assert\File(maxSize: '25M', mimeTypes: ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'])]
    private ?File $mascotTwoFile = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $mascotTwoUpdatedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $extraImageFileName = null;

    #[Vich\UploadableField(mapping: 'content_files', fileNameProperty: 'extraImageFileName')]
    #[Assert\File(maxSize: '25M', mimeTypes: ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'])]
    private ?File $extraImageFile = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $extraImageUpdatedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fifthImageFileName = null;

    #[Vich\UploadableField(mapping: 'content_files', fileNameProperty: 'fifthImageFileName')]
    #[Assert\File(maxSize: '25M', mimeTypes: ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'])]
    private ?File $fifthImageFile = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $fifthImageUpdatedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $documentFileName = null;

    #[Vich\UploadableField(mapping: 'content_files', fileNameProperty: 'documentFileName')]
    #[Assert\File(maxSize: '100M', mimeTypes: ['application/pdf'])]
    private ?File $documentFile = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $documentUpdatedAt = null;

    /** @var Collection<int, ServiceQrLink> */
    #[ORM\OneToMany(targetEntity: ServiceQrLink::class, mappedBy: 'page', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['priority' => 'ASC', 'id' => 'ASC'])]
    #[Groups('content:read')]
    public Collection $serviceQrLinks;

    #[ORM\Column(type: Types::JSON)]
    #[Groups('content:read')]
    public array $data = [];

    #[ORM\Column]
    #[Groups('content:read')]
    public bool $active = true;

    public function __construct() { $this->serviceQrLinks = new ArrayCollection(); }
    public function getId(): ?int { return $this->id; }
    public function __toString(): string { return $this->title ?: $this->type->value; }

    public function getImageFile(): ?File { return $this->imageFile; }
    public function setImageFile(?File $file): void { $this->imageFile = $file; if ($file) $this->imageUpdatedAt = new \DateTimeImmutable(); }
    public function getImageFileName(): ?string { return $this->imageFileName; }
    public function setImageFileName(?string $name): void { $this->imageFileName = $name; }
    #[Groups('content:read')]
    public function getImageUrl(): ?string { return $this->imageFileName ? '/uploads/content-files/'.$this->imageFileName : null; }
    public function getLogoFile(): ?File { return $this->logoFile; }
    public function setLogoFile(?File $file): void { $this->logoFile = $file; if ($file) $this->logoUpdatedAt = new \DateTimeImmutable(); }
    public function getLogoFileName(): ?string { return $this->logoFileName; }
    public function setLogoFileName(?string $name): void { $this->logoFileName = $name; }
    #[Groups('content:read')]
    public function getLogoUrl(): ?string { return $this->logoFileName ? '/uploads/content-files/'.$this->logoFileName : null; }
    public function getMascotTwoFile(): ?File { return $this->mascotTwoFile; }
    public function setMascotTwoFile(?File $file): void { $this->mascotTwoFile = $file; if ($file) $this->mascotTwoUpdatedAt = new \DateTimeImmutable(); }
    public function getMascotTwoFileName(): ?string { return $this->mascotTwoFileName; }
    public function setMascotTwoFileName(?string $name): void { $this->mascotTwoFileName = $name; }
    #[Groups('content:read')]
    public function getMascotTwoUrl(): ?string { return $this->mascotTwoFileName ? '/uploads/content-files/'.$this->mascotTwoFileName : null; }
    public function getExtraImageFile(): ?File { return $this->extraImageFile; }
    public function setExtraImageFile(?File $file): void { $this->extraImageFile = $file; if ($file) $this->extraImageUpdatedAt = new \DateTimeImmutable(); }
    public function getExtraImageFileName(): ?string { return $this->extraImageFileName; }
    public function setExtraImageFileName(?string $name): void { $this->extraImageFileName = $name; }
    #[Groups('content:read')]
    public function getExtraImageUrl(): ?string { return $this->extraImageFileName ? '/uploads/content-files/'.$this->extraImageFileName : null; }
    public function getFifthImageFile(): ?File { return $this->fifthImageFile; }
    public function setFifthImageFile(?File $file): void { $this->fifthImageFile = $file; if ($file) $this->fifthImageUpdatedAt = new \DateTimeImmutable(); }
    public function getFifthImageFileName(): ?string { return $this->fifthImageFileName; }
    public function setFifthImageFileName(?string $name): void { $this->fifthImageFileName = $name; }
    #[Groups('content:read')]
    public function getFifthImageUrl(): ?string { return $this->fifthImageFileName ? '/uploads/content-files/'.$this->fifthImageFileName : null; }
    public function getDocumentFile(): ?File { return $this->documentFile; }
    public function setDocumentFile(?File $file): void { $this->documentFile = $file; if ($file) $this->documentUpdatedAt = new \DateTimeImmutable(); }
    public function getDocumentFileName(): ?string { return $this->documentFileName; }
    public function setDocumentFileName(?string $name): void { $this->documentFileName = $name; }
    #[Groups('content:read')]
    public function getDocumentUrl(): ?string { return $this->documentFileName ? '/uploads/content-files/'.$this->documentFileName : null; }
    public function addServiceQrLink(ServiceQrLink $link): void { if (!$this->serviceQrLinks->contains($link)) { $this->serviceQrLinks->add($link); $link->page = $this; } }
    public function removeServiceQrLink(ServiceQrLink $link): void { $this->serviceQrLinks->removeElement($link); }

    public function getDataValue(string $key, mixed $default = null): mixed { return $this->data[$key] ?? $default; }
    public function setDataValue(string $key, mixed $value): void { $this->data[$key] = $value; }
    public function getAdvantages(): array { return $this->getDataValue('advantages', []); }
    public function setAdvantages(array $value): void { $this->setDataValue('advantages', array_values($value)); }
    public function getSourceUrl(): ?string { return $this->getDataValue('sourceUrl'); }
    public function setSourceUrl(?string $value): void { $this->setDataValue('sourceUrl', $value); }
    public function getReceptionDescription(): ?string { return $this->getDataValue('receptionDescription'); }
    public function setReceptionDescription(?string $value): void { $this->setDataValue('receptionDescription', $value); }
    public function getRegistryDescription(): ?string { return $this->getDataValue('registryDescription'); }
    public function setRegistryDescription(?string $value): void { $this->setDataValue('registryDescription', $value); }
    public function getNurseDescription(): ?string { return $this->getDataValue('nurseDescription'); }
    public function setNurseDescription(?string $value): void { $this->setDataValue('nurseDescription', $value); }
    public function getMainTerritoryDiningHallDescription(): ?string { return $this->getDataValue('mainTerritoryDiningHallDescription'); }
    public function setMainTerritoryDiningHallDescription(?string $value): void { $this->setDataValue('mainTerritoryDiningHallDescription', $value); }
    public function getBuildingSevenDiningHallDescription(): ?string { return $this->getDataValue('buildingSevenDiningHallDescription'); }
    public function setBuildingSevenDiningHallDescription(?string $value): void { $this->setDataValue('buildingSevenDiningHallDescription', $value); }
    public function getCafeDescription(): ?string { return $this->getDataValue('cafeDescription'); }
    public function setCafeDescription(?string $value): void { $this->setDataValue('cafeDescription', $value); }
    public function getPhytoBarDescription(): ?string { return $this->getDataValue('phytoBarDescription'); }
    public function setPhytoBarDescription(?string $value): void { $this->setDataValue('phytoBarDescription', $value); }
    public function getMainTerritoryInfrastructure(): array { return $this->getDataValue('mainTerritoryInfrastructure', []); }
    public function setMainTerritoryInfrastructure(array $value): void { $this->setDataValue('mainTerritoryInfrastructure', $this->normalizeTextList($value)); }
    public function getBuildingSevenInfrastructure(): array { return $this->getDataValue('buildingSevenInfrastructure', []); }
    public function setBuildingSevenInfrastructure(array $value): void { $this->setDataValue('buildingSevenInfrastructure', $this->normalizeTextList($value)); }
    public function getMainTerritoryDepartureTimes(): array { return $this->getDataValue('mainTerritoryDepartureTimes', []); }
    public function setMainTerritoryDepartureTimes(array $value): void { $this->setDataValue('mainTerritoryDepartureTimes', $this->normalizeTextList($value)); }
    public function getBuildingSevenDepartureTimes(): array { return $this->getDataValue('buildingSevenDepartureTimes', []); }
    public function setBuildingSevenDepartureTimes(array $value): void { $this->setDataValue('buildingSevenDepartureTimes', $this->normalizeTextList($value)); }
    public function getCheckInTime(): ?string { return $this->getDataValue('checkInTime'); }
    public function setCheckInTime(?string $value): void { $this->setDataValue('checkInTime', $value); }
    public function getCheckOutTime(): ?string { return $this->getDataValue('checkOutTime'); }
    public function setCheckOutTime(?string $value): void { $this->setDataValue('checkOutTime', $value); }
    public function getPlacementRules(): array { return $this->getDataValue('placementRules', []); }
    public function setPlacementRules(array $value): void { $this->setDataValue('placementRules', $this->normalizeTextList($value)); }
    public function getVisitorPassText(): ?string { return $this->getDataValue('visitorPassText'); }
    public function setVisitorPassText(?string $value): void { $this->setDataValue('visitorPassText', $value); }
    public function getSafetyRules(): array { return $this->getDataValue('safetyRules', []); }
    public function setSafetyRules(array $value): void { $this->setDataValue('safetyRules', $this->normalizeTextList($value)); }
    public function getMedicalProcedureRules(): array { return $this->getDataValue('medicalProcedureRules', []); }
    public function setMedicalProcedureRules(array $value): void { $this->setDataValue('medicalProcedureRules', $this->normalizeTextList($value)); }
    public function getMission(): ?string { return $this->getDataValue('mission'); }
    public function setMission(?string $value): void { $this->setDataValue('mission', $value); }
    public function getAboutSanatorium(): array { return $this->getDataValue('aboutSanatorium', []); }
    public function setAboutSanatorium(array $value): void { $this->setDataValue('aboutSanatorium', $this->normalizeTextList($value)); }
    public function getPrizeBenefits(): array { return $this->getDataValue('prizeBenefits', []); }
    public function setPrizeBenefits(array $value): void { $this->setDataValue('prizeBenefits', $this->normalizeTextList($value)); }
    public function getImportantNotices(): array { return $this->getDataValue('importantNotices', []); }
    public function setImportantNotices(array $value): void { $this->setDataValue('importantNotices', $this->normalizeTextList($value)); }
    public function getConnectRewards(): array { return $this->getDataValue('connectRewards', []); }
    public function setConnectRewards(array $value): void
    {
        $this->setDataValue('connectRewards', array_values(array_map(static fn (array $item): array => [
            'achievement' => trim((string) ($item['achievement'] ?? '')),
            'points' => (int) ($item['points'] ?? 0),
        ], $value)));
    }

    #[Assert\Callback]
    public function validateStructuredData(ExecutionContextInterface $context): void
    {
        foreach ($this->getConnectRewards() as $index => $reward) {
            if (($reward['achievement'] ?? '') === '') $context->buildViolation('Укажите достижение.')->atPath('connectRewards['.$index.'][achievement]')->addViolation();
            $points = (int) ($reward['points'] ?? 0);
            if ($points < 1 || $points > 5) $context->buildViolation('Количество баллов должно быть от 1 до 5.')->atPath('connectRewards['.$index.'][points]')->addViolation();
        }

        if ($this->type === ContentPageType::RESIDENCE_RULES) {
            foreach (['checkInTime' => $this->getCheckInTime(), 'checkOutTime' => $this->getCheckOutTime()] as $path => $time) {
                if ($time !== null && !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $time)) $context->buildViolation('Введите время в формате ЧЧ:ММ.')->atPath($path)->addViolation();
            }
        }

        if ($this->type === ContentPageType::TRANSFER) {
            foreach (['mainTerritoryDepartureTimes' => $this->getMainTerritoryDepartureTimes(), 'buildingSevenDepartureTimes' => $this->getBuildingSevenDepartureTimes()] as $path => $times) {
                foreach ($times as $index => $time) {
                    if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $time)) $context->buildViolation('Введите время в формате ЧЧ:ММ.')->atPath($path.'['.$index.']')->addViolation();
                }
            }
        }
    }

    private function normalizeTextList(array $value): array
    {
        return array_values(array_map('trim', array_filter($value, static fn (mixed $item): bool => is_string($item) && trim($item) !== '')));
    }
}
