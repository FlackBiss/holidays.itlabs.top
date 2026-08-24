<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Enum\SectionSlug;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity]
#[Vich\Uploadable]
#[ORM\Index(name: 'section_document_section_idx', columns: ['section', 'active', 'priority'])]
#[ApiResource(
    operations: [new Get(), new GetCollection(order: ['priority' => 'ASC', 'id' => 'ASC'])],
    normalizationContext: ['groups' => ['section-document:read']],
    paginationEnabled: false,
)]
#[ApiFilter(SearchFilter::class, properties: ['section' => 'exact', 'parent.id' => 'exact', 'title' => 'partial'])]
#[ApiFilter(BooleanFilter::class, properties: ['active'])]
#[ApiFilter(OrderFilter::class, properties: ['priority', 'title', 'id'])]
class SectionDocument
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    #[Groups('section-document:read')]
    private ?int $id = null;

    #[ORM\Column(enumType: SectionSlug::class)]
    #[Groups('section-document:read')]
    public SectionSlug $section = SectionSlug::GUEST_INFO;

    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    public ?self $parent = null;

    #[ORM\Column(length: 255)]
    #[Groups('section-document:read')]
    public string $title = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups('section-document:read')]
    public ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fileName = null;

    #[Vich\UploadableField(mapping: 'section_documents', fileNameProperty: 'fileName')]
    #[Assert\File(maxSize: '100M', mimeTypes: ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml', 'application/pdf'])]
    private ?File $file = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column]
    #[Groups('section-document:read')]
    public int $priority = 0;

    #[ORM\Column]
    #[Groups('section-document:read')]
    public bool $active = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    #[Groups('section-document:read')]
    public function getParentId(): ?int
    {
        return $this->parent?->getId();
    }

    #[Groups('section-document:read')]
    public function getUrl(): ?string
    {
        return $this->fileName ? '/uploads/section-documents/'.$this->fileName : null;
    }

    public function getFile(): ?File
    {
        return $this->file;
    }

    public function setFile(?File $file): void
    {
        $this->file = $file;
        if ($file) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function setFileName(?string $fileName): void
    {
        $this->fileName = $fileName;
    }

    public function __toString(): string
    {
        return $this->title ?: $this->section->label();
    }

    #[Assert\Callback]
    public function validateParent(ExecutionContextInterface $context): void
    {
        if ($this->file && in_array($this->section, [SectionSlug::GUEST_INFO, SectionSlug::PRICES], true) && $this->file->getMimeType() !== 'application/pdf') {
            $context->buildViolation('Для этого раздела необходимо загрузить PDF-файл.')->atPath('file')->addViolation();
        }
        if ($this->parent === $this) {
            $context->buildViolation('Документ не может быть собственным родителем.')->atPath('parent')->addViolation();
        } elseif ($this->parent && $this->parent->section !== $this->section) {
            $context->buildViolation('Родитель должен относиться к тому же разделу.')->atPath('parent')->addViolation();
        }
    }
}
