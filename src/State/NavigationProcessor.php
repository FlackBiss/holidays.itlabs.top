<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\NavigationRequest;
use App\ApiResource\NavigationRouteResult;
use App\Service\NavigationService;

final readonly class NavigationProcessor implements ProcessorInterface
{
    public function __construct(private NavigationService $navigation) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): NavigationRouteResult
    {
        if (!$data instanceof NavigationRequest) throw new \InvalidArgumentException('Некорректный запрос маршрута.');
        return $this->navigation->buildRoute($data);
    }
}
