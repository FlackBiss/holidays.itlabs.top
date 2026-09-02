<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity]
#[ORM\Index(name: 'taganrog_slider_image_page_idx', columns: ['page_id'])]
#[Vich\Uploadable]
class TaganrogSliderImage
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    #[Groups('content:read')]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'sliderImages')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public ?ContentPage $page = null;

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
    public function getFileName(): ?string { return $this->fileName; }
    public function setFileName(?string $fileName): void { $this->fileName = $fileName; }

    public function setFile(?File $file): void
    {
        $this->file = $file;
        if ($file) $this->updatedAt = new \DateTimeImmutable();
    }

    #[Groups('content:read')]
    public function getUrl(): ?string
    {
        return $this->fileName ? '/uploads/content-files/'.$this->fileName : null;
    }

    public function __toString(): string
    {
        return 'Фотография '.max(1, $this->priority);
    }
}
