<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class ContentRevision
{
    #[ORM\Id, ORM\Column]
    private int $id = 1;

    #[ORM\Column(length: 32)]
    public string $version;

    #[ORM\Column]
    public \DateTimeImmutable $updatedAt;

    public function __construct() { $this->touch(); }

    public function touch(): void
    {
        $this->version = bin2hex(random_bytes(16));
        $this->updatedAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }
}
