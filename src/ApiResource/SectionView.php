<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\State\SectionProvider;

#[ApiResource(
    operations: [
        new GetCollection(uriTemplate: '/sections', provider: SectionProvider::class),
        new Get(uriTemplate: '/sections/{slug}', provider: SectionProvider::class),
    ],
    paginationEnabled: false,
)]
final class SectionView
{
    #[ApiProperty(identifier: true)]
    public string $slug = '';

    public string $title = '';

    /** @var array<string, mixed> */
    public array $data = [];
}
