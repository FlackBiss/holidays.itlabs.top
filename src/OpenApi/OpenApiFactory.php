<?php

namespace App\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;

final readonly class OpenApiFactory implements OpenApiFactoryInterface
{
    public function __construct(private OpenApiFactoryInterface $decorated) {}

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);
        $paths = $openApi->getPaths();

        $paths->addPath('/api/map/search', (new PathItem())->withGet(new Operation(
            operationId: 'searchMapObjects',
            tags: ['Карта и навигация'],
            responses: ['200' => new Response('Упорядоченные результаты поиска'), '400' => new Response('Слишком короткий запрос')],
            summary: 'Поиск объектов карты',
            parameters: [
                new Parameter('q', 'query', 'Название или поисковый псевдоним', true, schema: ['type' => 'string', 'minLength' => 2]),
                new Parameter('plan', 'query', 'Идентификатор карты', false, schema: ['type' => 'integer']),
            ],
        )));
        $paths->addPath('/api/map/categories', (new PathItem())->withGet(new Operation(
            operationId: 'mapPlaceCategories',
            tags: ['Карта и навигация'],
            responses: ['200' => new Response('Статичные категории объектов для легенды карты')],
            summary: 'Категории легенды карты',
        )));
        $paths->addPath('/api/objects/search', (new PathItem())->withGet(new Operation(
            operationId: 'searchObjectsCompatibility',
            tags: ['Карта и навигация'],
            responses: ['200' => new Response('Результаты поиска объектов'), '400' => new Response('Слишком короткий запрос')],
            summary: 'Совместимый поиск объектов карты',
            parameters: [
                new Parameter('q', 'query', 'Название объекта', true, schema: ['type' => 'string', 'minLength' => 2]),
                new Parameter('plan', 'query', 'Идентификатор карты', false, schema: ['type' => 'integer']),
            ],
        )));
        $legacyRouteParameters = [
            new Parameter('from', 'query', 'Начальная точка', true, schema: ['type' => 'integer']),
            new Parameter('to', 'query', 'Конечная точка', true, schema: ['type' => 'integer']),
            new Parameter('routeType', 'query', '1 — обычный, 2 — доступный', false, schema: ['type' => 'integer', 'enum' => [1, 2]]),
        ];
        $paths->addPath('/api/nodes/navigate', (new PathItem())->withGet(new Operation(
            operationId: 'navigateNodesCompatibility',
            tags: ['Карта и навигация'],
            responses: ['200' => new Response('Массив точек маршрута'), '404' => new Response('Точка не найдена'), '422' => new Response('Маршрут не найден')],
            summary: 'Совместимый расчёт маршрута между узлами',
            parameters: $legacyRouteParameters,
        )));
        $paths->addPath('/api/nodes/qr-code', (new PathItem())->withGet(new Operation(
            operationId: 'nodeQrCompatibility',
            tags: ['Карта и навигация'],
            responses: ['200' => new Response('Мобильная ссылка и QR-код маршрута'), '404' => new Response('Объект назначения не найден')],
            summary: 'Совместимый перенос маршрута без PDF',
            parameters: $legacyRouteParameters,
        )));
        $paths->addPath('/api/node_types', (new PathItem())->withGet(new Operation(
            operationId: 'nodeTypesCompatibility',
            tags: ['Карта и навигация'],
            responses: ['200' => new Response('Типы маршрутов')],
            summary: 'Типы маршрутов для старого редактора',
        )));
        $paths->addPath('/api/navigation/qr/{placeId}', (new PathItem())->withGet(new Operation(
            operationId: 'navigationQr',
            tags: ['Карта и навигация'],
            responses: ['200' => new Response('PNG QR-код мобильного маршрута'), '404' => new Response('Объект не найден')],
            summary: 'QR-код переноса маршрута на телефон',
            parameters: [new Parameter('placeId', 'path', 'Идентификатор объекта назначения', true, schema: ['type' => 'integer'])],
        )));
        $paths->addPath('/api/terminals/{code}/ping', (new PathItem())->withPost(new Operation(
            operationId: 'terminalPing',
            tags: ['Карта и навигация'],
            responses: ['200' => new Response('Терминал отмечен как активный'), '404' => new Response('Терминал не найден')],
            summary: 'Сигнал активности терминала',
            parameters: [new Parameter('code', 'path', 'Код терминала', true, schema: ['type' => 'string'])],
        )));

        return $openApi;
    }
}
