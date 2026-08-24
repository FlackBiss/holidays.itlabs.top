<?php

namespace App\Entity\Traits;

use App\Enum\MediaType;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

trait SingleUploadTrait
{
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fileName = null;

    #[Vich\UploadableField(mapping: 'content_files', fileNameProperty: 'fileName')]
    #[Assert\File(maxSize: '100M', mimeTypes: ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml', 'application/pdf', 'video/mp4', 'video/webm'])]
    private ?File $file = null;

    #[ORM\Column(enumType: MediaType::class, nullable: true)]
    #[Groups('content:read', 'settings:read', 'map:read')]
    public ?MediaType $fileType = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $fileUpdatedAt = null;

    public function getFile(): ?File { return $this->file; }
    public function getFileName(): ?string { return $this->fileName; }
    public function setFileName(?string $fileName): void { $this->fileName = $fileName; }

    public function setFile(?File $file): void
    {
        $this->file = $file;
        if (!$file) return;
        $mime = (string) $file->getMimeType();
        $this->fileType = str_starts_with($mime, 'video/') ? MediaType::VIDEO : MediaType::IMAGE;
        $this->fileUpdatedAt = new \DateTimeImmutable();
    }

    #[Groups('content:read', 'settings:read', 'map:read')]
    public function getFileUrl(): ?string
    {
        return $this->fileName ? '/uploads/content-files/'.$this->fileName : null;
    }
}
