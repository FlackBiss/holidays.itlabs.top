<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\State\NavigationProcessor;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(operations: [new Post(uriTemplate: '/navigation/routes', processor: NavigationProcessor::class, output: NavigationRouteResult::class)])]
class NavigationRequest
{
    #[Assert\NotNull, Assert\Positive]
    public ?int $destinationPlaceId = null;

    #[Assert\Positive]
    public ?int $fromNodeId = null;

    public ?string $terminalCode = null;

    #[Assert\Range(min: -90, max: 90)]
    public ?float $latitude = null;

    #[Assert\Range(min: -180, max: 180)]
    public ?float $longitude = null;

    public bool $accessible = false;
}
