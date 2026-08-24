<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity]
#[Vich\Uploadable]
#[ORM\UniqueConstraint(name: 'uniq_public_transport_route_number', columns: ['route_number'])]
#[ApiResource(operations: [new Get(), new GetCollection(order: ['priority' => 'ASC', 'id' => 'ASC'])], normalizationContext: ['groups' => ['public-transport:read']], paginationEnabled: false)]
#[ApiFilter(SearchFilter::class, properties: ['routeNumber' => 'exact'])]
#[ApiFilter(BooleanFilter::class, properties: ['active'])]
#[ApiFilter(OrderFilter::class, properties: ['priority', 'routeNumber', 'id'])]
class PublicTransportRoute
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    #[Groups('public-transport:read')]
    private ?int $id = null;

    #[ORM\Column(length: 32)]
    #[Groups('public-transport:read')]
    public string $routeNumber = '';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fileName = null;

    #[Vich\UploadableField(mapping: 'section_documents', fileNameProperty: 'fileName')]
    #[Assert\File(maxSize: '100M', mimeTypes: ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml', 'application/pdf'])]
    private ?File $file = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: Types::JSON)]
    #[Groups('public-transport:read')]
    private array $schedules = [];

    #[ORM\Column]
    #[Groups('public-transport:read')]
    public int $priority = 0;

    #[ORM\Column]
    #[Groups('public-transport:read')]
    public bool $active = true;

    public function getId(): ?int { return $this->id; }
    public function getFile(): ?File { return $this->file; }
    public function setFile(?File $file): void { $this->file = $file; if ($file) $this->updatedAt = new \DateTimeImmutable(); }
    public function getFileName(): ?string { return $this->fileName; }
    public function setFileName(?string $fileName): void { $this->fileName = $fileName; }
    #[Groups('public-transport:read')]
    public function getRouteMapUrl(): ?string { return $this->fileName ? '/uploads/section-documents/'.$this->fileName : null; }
    public function getSchedules(): array { return $this->schedules; }
    public function setSchedules(array $schedules): void
    {
        $this->schedules = array_values(array_map(static fn (array $schedule): array => [
            'stopName' => trim((string) ($schedule['stopName'] ?? '')),
            'days' => trim((string) ($schedule['days'] ?? '')),
            'times' => array_values(array_map('trim', array_filter($schedule['times'] ?? [], static fn (mixed $time): bool => is_string($time) && trim($time) !== ''))),
        ], $schedules));
    }
    public function __toString(): string { return $this->routeNumber ? 'Автобус '.$this->routeNumber : 'Новый автобус'; }

    #[Assert\Callback]
    public function validateSchedules(ExecutionContextInterface $context): void
    {
        foreach ($this->schedules as $index => $schedule) {
            if (($schedule['stopName'] ?? '') === '') $context->buildViolation('Укажите остановку.')->atPath('schedules['.$index.'][stopName]')->addViolation();
            if (($schedule['days'] ?? '') === '') $context->buildViolation('Укажите дни отправления.')->atPath('schedules['.$index.'][days]')->addViolation();
            if (($schedule['times'] ?? []) === []) $context->buildViolation('Добавьте хотя бы одно время отправления.')->atPath('schedules['.$index.'][times]')->addViolation();
            foreach (($schedule['times'] ?? []) as $timeIndex => $time) {
                if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', (string) $time)) $context->buildViolation('Введите время в формате ЧЧ:ММ.')->atPath('schedules['.$index.'][times]['.$timeIndex.']')->addViolation();
            }
        }
    }
}
