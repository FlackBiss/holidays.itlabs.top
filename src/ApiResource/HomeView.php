<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\HomeProvider;

#[ApiResource(
    operations: [new Get(uriTemplate: '/home', provider: HomeProvider::class)],
)]
final class HomeView
{
    /** @var array<string, mixed>|null */
    public ?array $settings = null;

    /** @var array<string, mixed> */
    public array $weather = [];

    /** @var list<array<string, mixed>> */
    public array $standby = [];

    /** @var array{iso: string, date: string, time: string, timezone: string, timestamp: int} */
    public array $serverDateTime = [];
}
