<?php

namespace App\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\MediaType;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\RequestBody;
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
        $paths->addPath('/api/settings/exit-password', (new PathItem())->withGet(new Operation(
            operationId: 'getExitPassword',
            tags: ['Настройки'],
            responses: ['200' => new Response('Текущий пароль для выхода из приложения')],
            summary: 'Получение пароля для выхода из приложения',
        )));

        $planParameter = new Parameter('planId', 'path', 'Идентификатор схемы карты', true, schema: ['type' => 'integer', 'minimum' => 1]);
        $calibrationRequest = $this->jsonRequest([
            'type' => 'object',
            'required' => ['method', 'controlPoints'],
            'properties' => [
                'method' => ['type' => 'string', 'enum' => ['piecewise_affine']],
                'controlPoints' => [
                    'type' => 'array', 'minItems' => 4, 'maxItems' => 15,
                    'items' => [
                        'type' => 'object',
                        'required' => ['x', 'y', 'latitude', 'longitude', 'position'],
                        'properties' => [
                            'x' => ['type' => 'number'], 'y' => ['type' => 'number'],
                            'latitude' => ['type' => 'number', 'minimum' => -90, 'maximum' => 90],
                            'longitude' => ['type' => 'number', 'minimum' => -180, 'maximum' => 180],
                            'position' => ['type' => 'integer', 'minimum' => 1],
                        ],
                    ],
                ],
            ],
        ], [
            'method' => 'piecewise_affine',
            'controlPoints' => [
                ['x' => 0, 'y' => 0, 'latitude' => 56.034123, 'longitude' => 37.602456, 'position' => 1],
                ['x' => 1960, 'y' => 0, 'latitude' => 56.034123, 'longitude' => 37.612456, 'position' => 2],
                ['x' => 1960, 'y' => 885, 'latitude' => 56.029123, 'longitude' => 37.612456, 'position' => 3],
                ['x' => 0, 'y' => 885, 'latitude' => 56.029123, 'longitude' => 37.602456, 'position' => 4],
            ],
        ]);
        $calibrationResponse = $this->jsonResponse('Сохранённая геокалибровка', [
            'id' => 1, 'planId' => 5, 'method' => 'piecewise_affine', 'version' => 2,
            'controlPoints' => [], 'createdAt' => '2026-09-02T12:00:00+04:00', 'updatedAt' => '2026-09-02T12:10:00+04:00',
        ], $this->calibrationSchema());
        $paths->addPath('/api/map_plans/{planId}/geo_calibration', (new PathItem())
            ->withGet(new Operation(
                operationId: 'getMapGeoCalibration', tags: ['Геокалибровка карты'],
                responses: ['200' => $this->jsonResponse('Калибровка либо calibration: null', ['calibration' => null], [
                    'type' => 'object', 'required' => ['calibration'],
                    'properties' => ['calibration' => ['oneOf' => [$this->calibrationSchema(), ['type' => 'null']]]],
                ]), '404' => new Response('Карта не найдена')],
                summary: 'Получение сохранённой геокалибровки', parameters: [$planParameter],
            ))
            ->withPut(new Operation(
                operationId: 'putMapGeoCalibration', tags: ['Геокалибровка карты'],
                responses: ['200' => $calibrationResponse, '404' => new Response('Карта не найдена'), '422' => new Response('Некорректные контрольные точки')],
                summary: 'Сохранение новой версии геокалибровки', parameters: [$planParameter], requestBody: $calibrationRequest,
            ))
            ->withDelete(new Operation(
                operationId: 'deleteMapGeoCalibration', tags: ['Геокалибровка карты'],
                responses: ['204' => new Response('Калибровка удалена; координаты узлов сохранены'), '404' => new Response('Карта не найдена')],
                summary: 'Удаление настроек геокалибровки', parameters: [$planParameter],
            ))
        );
        $paths->addPath('/api/map_plans/{planId}/geo_calibration/preview', (new PathItem())->withPost(new Operation(
            operationId: 'previewMapGeoCalibration', tags: ['Геокалибровка карты'],
            responses: [
                '200' => $this->jsonResponse('Предварительный расчёт без изменения базы', [
                    'method' => 'piecewise_affine', 'controlPointCount' => 8, 'totalNodeCount' => 223,
                    'calculableNodeCount' => 223, 'uncoveredNodeCount' => 0, 'canApply' => true,
                    'metrics' => ['medianErrorMeters' => 5.2, 'p95ErrorMeters' => 12.8, 'maximumErrorMeters' => 18.4],
                    'warnings' => [],
                    'nodes' => [[
                        'id' => 233, 'x' => 1200.03, 'y' => 424.7, 'currentLatitude' => null, 'currentLongitude' => null,
                        'calculatedLatitude' => 56.034123, 'calculatedLongitude' => 37.602456, 'status' => 'calculated',
                    ]],
                ], $this->previewSchema()),
                '404' => new Response('Карта не найдена'), '422' => new Response('Некорректные контрольные точки'),
            ],
            summary: 'Предварительный расчёт координат и погрешности', parameters: [$planParameter], requestBody: $calibrationRequest,
        )));
        $paths->addPath('/api/map_plans/{planId}/geo_calibration/apply', (new PathItem())->withPost(new Operation(
            operationId: 'applyMapGeoCalibration', tags: ['Геокалибровка карты'],
            responses: [
                '200' => $this->jsonResponse('Результат транзакционного применения', [
                    'version' => 2, 'totalNodeCount' => 223, 'updatedNodeCount' => 223, 'skippedManualCount' => 0, 'unchangedNodeCount' => 0,
                ], $this->applyResultSchema()),
                '404' => new Response('Карта или калибровка не найдена'), '409' => new Response('Устаревшая версия калибровки'),
                '422' => new Response('Не все узлы можно безопасно рассчитать'),
            ],
            summary: 'Транзакционное применение калибровки ко всем узлам', parameters: [$planParameter],
            requestBody: $this->jsonRequest([
                'type' => 'object', 'required' => ['version'],
                'properties' => [
                    'version' => ['type' => 'integer', 'minimum' => 1],
                    'overwriteCalibrated' => ['type' => 'boolean', 'default' => true],
                    'overwriteManual' => ['type' => 'boolean', 'default' => false],
                ],
            ], ['version' => 2, 'overwriteCalibrated' => true, 'overwriteManual' => false]),
        )));

        return $openApi;
    }

    /** @param array<string, mixed> $schema @param array<string, mixed> $example */
    private function jsonRequest(array $schema, array $example): RequestBody
    {
        return new RequestBody(
            description: 'JSON',
            content: new \ArrayObject(['application/json' => new MediaType(new \ArrayObject($schema), $example)]),
            required: true,
        );
    }

    /** @param array<string, mixed> $example @param array<string, mixed>|null $schema */
    private function jsonResponse(string $description, array $example, ?array $schema = null): Response
    {
        return new Response($description, new \ArrayObject([
            'application/json' => new MediaType($schema === null ? null : new \ArrayObject($schema), $example),
        ]));
    }

    /** @return array<string, mixed> */
    private function calibrationSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['id', 'planId', 'method', 'version', 'controlPoints', 'createdAt', 'updatedAt'],
            'properties' => [
                'id' => ['type' => 'integer'], 'planId' => ['type' => 'integer'],
                'method' => ['type' => 'string', 'enum' => ['piecewise_affine']], 'version' => ['type' => 'integer', 'minimum' => 1],
                'controlPoints' => ['type' => 'array', 'items' => [
                    'type' => 'object', 'required' => ['id', 'x', 'y', 'latitude', 'longitude', 'position'],
                    'properties' => [
                        'id' => ['type' => 'integer'], 'x' => ['type' => 'number'], 'y' => ['type' => 'number'],
                        'latitude' => ['type' => 'number'], 'longitude' => ['type' => 'number'], 'position' => ['type' => 'integer'],
                    ],
                ]],
                'createdAt' => ['type' => 'string', 'format' => 'date-time'], 'updatedAt' => ['type' => 'string', 'format' => 'date-time'],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function previewSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['method', 'controlPointCount', 'totalNodeCount', 'calculableNodeCount', 'uncoveredNodeCount', 'canApply', 'metrics', 'warnings', 'nodes'],
            'properties' => [
                'method' => ['type' => 'string', 'enum' => ['piecewise_affine']],
                'controlPointCount' => ['type' => 'integer'], 'totalNodeCount' => ['type' => 'integer'],
                'calculableNodeCount' => ['type' => 'integer'], 'uncoveredNodeCount' => ['type' => 'integer'], 'canApply' => ['type' => 'boolean'],
                'metrics' => ['type' => 'object', 'required' => ['medianErrorMeters', 'p95ErrorMeters', 'maximumErrorMeters'], 'properties' => [
                    'medianErrorMeters' => ['type' => ['number', 'null']], 'p95ErrorMeters' => ['type' => ['number', 'null']],
                    'maximumErrorMeters' => ['type' => ['number', 'null']],
                ]],
                'warnings' => ['type' => 'array', 'items' => ['type' => 'string']],
                'nodes' => ['type' => 'array', 'items' => [
                    'type' => 'object',
                    'required' => ['id', 'x', 'y', 'currentLatitude', 'currentLongitude', 'calculatedLatitude', 'calculatedLongitude', 'status'],
                    'properties' => [
                        'id' => ['type' => ['integer', 'null']], 'x' => ['type' => 'number'], 'y' => ['type' => 'number'],
                        'currentLatitude' => ['type' => ['number', 'null']], 'currentLongitude' => ['type' => ['number', 'null']],
                        'calculatedLatitude' => ['type' => ['number', 'null']], 'calculatedLongitude' => ['type' => ['number', 'null']],
                        'status' => ['type' => 'string', 'enum' => ['calculated', 'outside_control_hull', 'invalid_coordinates', 'skipped_manual']],
                    ],
                ]],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function applyResultSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['version', 'totalNodeCount', 'updatedNodeCount', 'skippedManualCount', 'unchangedNodeCount'],
            'properties' => [
                'version' => ['type' => 'integer'], 'totalNodeCount' => ['type' => 'integer'],
                'updatedNodeCount' => ['type' => 'integer'], 'skippedManualCount' => ['type' => 'integer'],
                'unchangedNodeCount' => ['type' => 'integer'],
            ],
        ];
    }
}
