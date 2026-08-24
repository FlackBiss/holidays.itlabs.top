<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity]
#[Vich\Uploadable]
class ServiceQrLink
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    #[Groups('content:read')]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'serviceQrLinks')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public ?ContentPage $page = null;

    #[ORM\Column(length: 255)]
    #[Groups('content:read')]
    public string $title = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups('content:read')]
    public ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fileName = null;

    #[Vich\UploadableField(mapping: 'content_files', fileNameProperty: 'fileName')]
    #[Assert\File(maxSize: '25M', mimeTypes: ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'])]
    private ?File $file = null;

    #[ORM\Column]
    #[Groups('content:read')]
    public int $priority = 0;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function getId(): ?int { return $this->id; }
    public function getFile(): ?File { return $this->file; }
    public function setFile(?File $file): void { $this->file = $file; if ($file) $this->updatedAt = new \DateTimeImmutable(); }
    public function getFileName(): ?string { return $this->fileName; }
    public function setFileName(?string $name): void { $this->fileName = $name; }
    #[Groups('content:read')]
    public function getUrl(): ?string { return $this->fileName ? '/uploads/content-files/'.$this->fileName : null; }
    public function __toString(): string { return $this->title ?: 'QR-код'; }
}
