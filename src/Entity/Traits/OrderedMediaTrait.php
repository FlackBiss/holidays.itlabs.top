<?php

namespace App\Entity\Traits;

use App\Enum\MediaType;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

trait OrderedMediaTrait
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    #[Groups(['content:read', 'map:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['content:read', 'map:read'])]
    public string $title = '';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fileName = null;

    #[Vich\UploadableField(mapping: 'section_media', fileNameProperty: 'fileName')]
    #[Assert\File(maxSize: '100M', mimeTypes: ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml', 'video/mp4', 'video/webm'])]
    private ?File $file = null;

    #[ORM\Column(enumType: MediaType::class)]
    #[Groups(['content:read', 'map:read'])]
    public MediaType $type = MediaType::IMAGE;

    #[ORM\Column]
    #[Groups(['content:read', 'map:read'])]
    public int $priority = 0;

    #[ORM\Column]
    #[Groups(['content:read', 'map:read'])]
    public bool $active = true;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function getId(): ?int { return $this->id; }
    public function getFile(): ?File { return $this->file; }
    public function getFileName(): ?string { return $this->fileName; }
    public function setFileName(?string $fileName): void { $this->fileName = $fileName; }

    public function setFile(?File $file): void
    {
        $this->file = $file;
        if (!$file) return;
        $mime = (string) $file->getMimeType();
        $this->type = str_starts_with($mime, 'video/') ? MediaType::VIDEO : MediaType::IMAGE;
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[Groups(['content:read', 'map:read'])]
    public function getUrl(): ?string
    {
        return $this->fileName ? '/uploads/section-media/'.$this->fileName : null;
    }

    public function getTypeLabel(): string { return $this->type->label(); }

    public function __toString(): string { return $this->title ?: 'Новый материал'; }
}
