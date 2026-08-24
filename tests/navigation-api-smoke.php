<?php

declare(strict_types=1);

/** Полный HTTP-аудит редактора карты с совместимостью API Петровского. */
$baseUrl = rtrim($argv[1] ?? 'http://holidays.local', '/');
$checks = 0;
$failures = [];
$createdNodes = [];
$createdRoads = [];
$createdAreas = [];
$originalObject = null;
$originalTerminal = null;

function apiRequest(string $method, string $url, ?array $json = null): array
{
    $handle = curl_init($url);
    $headers = ['Accept: application/json'];
    if ($json !== null) {
        $headers[] = 'Content-Type: '.($method === 'PATCH' ? 'application/merge-patch+json' : 'application/json');
        curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($json, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }
    curl_setopt_array($handle, [CURLOPT_CUSTOMREQUEST => $method, CURLOPT_HTTPHEADER => $headers, CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_TIMEOUT => 30]);
    $raw = curl_exec($handle);
    if ($raw === false) throw new RuntimeException(curl_error($handle));
    $status = curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
    $headerSize = curl_getinfo($handle, CURLINFO_HEADER_SIZE);
    curl_close($handle);
    return ['status' => $status, 'body' => substr($raw, $headerSize)];
}

function body(array $response): array
{
    $data = json_decode($response['body'], true);
    return is_array($data) ? $data : [];
}

function collection(array $response): array
{
    $data = body($response);
    return array_is_list($data) ? $data : ($data['member'] ?? $data['hydra:member'] ?? []);
}

function audit(bool $condition, string $message): void
{
    global $checks, $failures;
    ++$checks;
    if (!$condition) { $failures[] = $message; fwrite(STDERR, "FAIL: {$message}\n"); }
}

function status(array $response, int|array $expected, string $message): void
{
    $expected = (array) $expected;
    audit(in_array($response['status'], $expected, true), sprintf('%s: HTTP %d, ожидался %s; %s', $message, $response['status'], implode('/', $expected), mb_substr($response['body'], 0, 240)));
}

try {
    $floorsResponse = apiRequest('GET', $baseUrl.'/api/floors');
    status($floorsResponse, 200, 'GET floors');
    $floors = collection($floorsResponse);
    $floor = $floors[0] ?? [];
    $floorId = $floor['id'] ?? null;
    audit(is_int($floorId) && !empty($floor['mapImage']), 'Карта доступна как floor с изображением');
    if (!is_int($floorId)) throw new RuntimeException('Нет карты для аудита.');
    status(apiRequest('GET', $baseUrl.'/api/floors/'.$floorId), 200, 'GET floor item');

    foreach (['areas', 'points', 'nodes', 'roads', 'objects', 'terminals', 'node_types'] as $resource) {
        status(apiRequest('GET', $baseUrl.'/api/'.$resource), 200, 'GET '.$resource);
    }

    $pointAResponse = apiRequest('POST', $baseUrl.'/api/points', ['floor' => $floorId, 'name' => '__audit_point_a', 'x' => 910.0, 'y' => 910.0, 'latitude' => 56.03400, 'longitude' => 37.60200]);
    status($pointAResponse, 201, 'POST point в формате Петровского');
    $pointA = body($pointAResponse)['id'] ?? null;
    if (!is_int($pointA)) throw new RuntimeException('Не создана точка A.');
    $createdNodes[] = $pointA;

    $nodeBResponse = apiRequest('POST', $baseUrl.'/api/nodes', ['point' => ['floor' => $floorId, 'x' => 930.0, 'y' => 910.0, 'latitude' => 56.03400, 'longitude' => 37.60220], 'name' => '__audit_node_b', 'nodes' => [$pointA], 'types' => [1, 2]]);
    status($nodeBResponse, 201, 'POST node с вложенными point/nodes/types');
    $nodeBData = body($nodeBResponse);
    $nodeB = $nodeBData['id'] ?? null;
    if (!is_int($nodeB)) throw new RuntimeException('Не создан узел B.');
    $createdNodes[] = $nodeB;
    audit(($nodeBData['point']['floor'] ?? null) === $floorId && in_array($pointA, $nodeBData['nodes'] ?? [], true), 'Ответ node совместим со старой рисовалкой');

    $pointCResponse = apiRequest('POST', $baseUrl.'/api/points', ['planId' => $floorId, 'name' => '__audit_point_c', 'x' => 950.0, 'y' => 910.0, 'latitude' => 56.03400, 'longitude' => 37.60240]);
    status($pointCResponse, 201, 'POST point в новом формате');
    $pointC = body($pointCResponse)['id'] ?? null;
    if (!is_int($pointC)) throw new RuntimeException('Не создана точка C.');
    $createdNodes[] = $pointC;

    status(apiRequest('PATCH', $baseUrl.'/api/points/'.$pointA, ['x' => 911.0]), 200, 'PATCH point');
    status(apiRequest('PATCH', $baseUrl.'/api/nodes/'.$nodeB, ['point' => ['x' => 931.0], 'nodes' => [$pointA], 'types' => [1, 2]]), 200, 'PATCH node в формате Петровского');
    status(apiRequest('GET', $baseUrl.'/api/points/'.$pointA), 200, 'GET point item');
    status(apiRequest('GET', $baseUrl.'/api/nodes/'.$nodeB), 200, 'GET node item');

    $legacyPathResponse = apiRequest('GET', $baseUrl.'/api/nodes/navigate?from='.$nodeB.'&to='.$pointA.'&routeType=1');
    status($legacyPathResponse, 200, 'GET legacy node route');
    audit(count(body($legacyPathResponse)) === 2, 'Старый маршрут содержит обе точки');

    $roadResponse = apiRequest('POST', $baseUrl.'/api/roads', ['planId' => $floorId, 'fromNodeId' => $pointA, 'toNodeId' => $pointC, 'bidirectional' => false, 'accessible' => false]);
    status($roadResponse, 201, 'POST road');
    $roadId = body($roadResponse)['id'] ?? null;
    if (!is_int($roadId)) throw new RuntimeException('Не создана дорога.');
    $createdRoads[] = $roadId;
    status(apiRequest('GET', $baseUrl.'/api/roads/'.$roadId), 200, 'GET road item');
    status(apiRequest('PATCH', $baseUrl.'/api/roads/'.$roadId, ['bidirectional' => true, 'accessible' => true, 'distanceMeters' => 20.0]), 200, 'PATCH road');

    $areaResponse = apiRequest('POST', $baseUrl.'/api/areas', ['floor' => $floorId, 'title' => '__audit_area', 'points' => [
        ['floor' => $floorId, 'x' => 900, 'y' => 900], ['floor' => $floorId, 'x' => 970, 'y' => 900], ['floor' => $floorId, 'x' => 970, 'y' => 970],
    ]]);
    status($areaResponse, 201, 'POST area в формате Петровского');
    $areaId = body($areaResponse)['id'] ?? null;
    if (!is_int($areaId)) throw new RuntimeException('Не создана область.');
    $createdAreas[] = $areaId;
    status(apiRequest('GET', $baseUrl.'/api/areas/'.$areaId), 200, 'GET area item');
    status(apiRequest('PATCH', $baseUrl.'/api/areas/'.$areaId, ['title' => '__audit_area_updated', 'points' => [
        ['floor' => $floorId, 'x' => 901, 'y' => 901], ['floor' => $floorId, 'x' => 971, 'y' => 901], ['floor' => $floorId, 'x' => 971, 'y' => 971],
    ]]), 200, 'PATCH area');

    $objects = collection(apiRequest('GET', $baseUrl.'/api/objects'));
    $object = $objects[0] ?? [];
    $objectId = $object['id'] ?? null;
    audit(is_int($objectId), 'Есть объект для проверки привязки');
    if (is_int($objectId)) {
        $originalObject = ['id' => $objectId, 'node' => $object['node'] ?? null, 'area' => $object['area'] ?? null];
        $placed = apiRequest('PATCH', $baseUrl.'/api/objects/'.$objectId, ['node' => $pointA, 'area' => $areaId]);
        status($placed, 200, 'PATCH object в формате tenant Петровского');
        $placedData = body($placed);
        audit(($placedData['node'] ?? null) === $pointA && ($placedData['area'] ?? null) === $areaId, 'Object возвращает скалярные node/area');
        status(apiRequest('GET', $baseUrl.'/api/objects/'.$objectId), 200, 'GET object item');

        $route = apiRequest('POST', $baseUrl.'/api/navigation/routes', ['destinationPlaceId' => $objectId, 'fromNodeId' => $nodeB]);
        status($route, 201, 'POST navigation from node');
        audit(count(body($route)['points'] ?? []) >= 2, 'Маршрут по расчерченной сети построен');

        $gps = apiRequest('POST', $baseUrl.'/api/navigation/routes', ['destinationPlaceId' => $objectId, 'latitude' => 56.03400, 'longitude' => 37.60215]);
        status($gps, 201, 'POST navigation from phone GPS');
        audit(isset(body($gps)['snappedPosition']), 'GPS привязан к дороге');

        $legacyQr = apiRequest('GET', $baseUrl.'/api/nodes/qr-code?from='.$nodeB.'&to='.$pointA.'&routeType=1');
        status($legacyQr, 200, 'GET legacy QR transfer');
        audit(str_contains((string) (body($legacyQr)['mobileUrl'] ?? ''), 'destination='), 'Старый QR-метод возвращает мобильную веб-ссылку');
    }

    $terminals = collection(apiRequest('GET', $baseUrl.'/api/terminals'));
    $terminal = $terminals[0] ?? [];
    $terminalId = $terminal['id'] ?? null;
    if (is_int($terminalId)) {
        $originalTerminal = ['id' => $terminalId, 'node' => $terminal['node'] ?? null, 'area' => $terminal['area'] ?? null];
        $placedTerminal = apiRequest('PATCH', $baseUrl.'/api/terminals/'.$terminalId, ['node' => $pointA, 'area' => $areaId]);
        status($placedTerminal, 200, 'PATCH terminal в формате Петровского');
        audit((body($placedTerminal)['node'] ?? null) === $pointA && (body($placedTerminal)['area'] ?? null) === $areaId, 'Terminal возвращает скалярные node/area');
        status(apiRequest('GET', $baseUrl.'/api/terminals/'.$terminalId), 200, 'GET terminal item');
    }

    status(apiRequest('GET', $baseUrl.'/api/objects/search?q='.rawurlencode('мед')), 200, 'GET objects search compatibility');
    status(apiRequest('POST', $baseUrl.'/api/points', ['x' => 1, 'y' => 2]), [400, 422], 'Point without floor rejected');
    status(apiRequest('POST', $baseUrl.'/api/points', ['floor' => 2147483647, 'x' => 1, 'y' => 2]), 404, 'Unknown floor rejected');
    status(apiRequest('POST', $baseUrl.'/api/points', ['floor' => $floorId, 'x' => 1, 'y' => 2, 'latitude' => 91]), 422, 'Invalid latitude rejected');
    status(apiRequest('POST', $baseUrl.'/api/nodes', ['point' => ['floor' => $floorId, 'x' => 1, 'y' => 2, 'longitude' => 181]]), 400, 'Invalid nested longitude rejected');
    status(apiRequest('PATCH', $baseUrl.'/api/nodes/'.$nodeB, ['nodes' => [$nodeB]]), 400, 'Self-linked node rejected');
    status(apiRequest('POST', $baseUrl.'/api/roads', ['planId' => $floorId, 'fromNodeId' => $pointA]), 400, 'Incomplete road rejected');
    status(apiRequest('POST', $baseUrl.'/api/roads', ['planId' => $floorId, 'fromNodeId' => $pointA, 'toNodeId' => $pointA]), 400, 'Self-loop road rejected');
    status(apiRequest('POST', $baseUrl.'/api/roads', ['planId' => $floorId, 'fromNodeId' => $pointA, 'toNodeId' => 2147483647]), 404, 'Road with unknown node rejected');
    status(apiRequest('POST', $baseUrl.'/api/areas', ['floor' => $floorId, 'points' => [['x' => 1, 'y' => 1], ['x' => 2, 'y' => 2]]]), 422, 'Area with fewer than three points rejected');
    status(apiRequest('POST', $baseUrl.'/api/areas', ['floor' => $floorId, 'points' => [['floor' => 2147483647, 'x' => 1, 'y' => 1], ['x' => 2, 'y' => 1], ['x' => 2, 'y' => 2]]]), 400, 'Area point on another floor rejected');
    status(apiRequest('GET', $baseUrl.'/api/nodes/navigate?from=0&to=0'), 400, 'Legacy route without nodes rejected');
    status(apiRequest('GET', $baseUrl.'/api/nodes/navigate?from=2147483646&to=2147483647'), 404, 'Legacy route with unknown nodes rejected');
    status(apiRequest('POST', $baseUrl.'/api/navigation/routes', ['destinationPlaceId' => $objectId ?? 0, 'latitude' => 56.0]), 400, 'Partial GPS rejected');

    if (is_array($originalTerminal)) {
        status(apiRequest('PATCH', $baseUrl.'/api/terminals/'.$originalTerminal['id'], ['node' => $originalTerminal['node'], 'area' => $originalTerminal['area']]), 200, 'Restore terminal placement');
        $originalTerminal = null;
    }
    if (is_array($originalObject)) {
        status(apiRequest('PATCH', $baseUrl.'/api/objects/'.$originalObject['id'], ['node' => $originalObject['node'], 'area' => $originalObject['area']]), 200, 'Restore object placement');
        $originalObject = null;
    }
    status(apiRequest('DELETE', $baseUrl.'/api/roads/'.$roadId), 204, 'DELETE road');
    $createdRoads = array_values(array_diff($createdRoads, [$roadId]));
    status(apiRequest('DELETE', $baseUrl.'/api/areas/'.$areaId), 204, 'DELETE area');
    $createdAreas = array_values(array_diff($createdAreas, [$areaId]));
    foreach ([$pointC, $nodeB, $pointA] as $nodeId) {
        status(apiRequest('DELETE', $baseUrl.'/api/nodes/'.$nodeId), 204, 'DELETE node '.$nodeId);
        $createdNodes = array_values(array_diff($createdNodes, [$nodeId]));
    }
} catch (Throwable $error) {
    $failures[] = $error::class.': '.$error->getMessage();
} finally {
    if (is_array($originalTerminal)) apiRequest('PATCH', $baseUrl.'/api/terminals/'.$originalTerminal['id'], ['node' => $originalTerminal['node'], 'area' => $originalTerminal['area']]);
    if (is_array($originalObject)) apiRequest('PATCH', $baseUrl.'/api/objects/'.$originalObject['id'], ['node' => $originalObject['node'], 'area' => $originalObject['area']]);
    foreach (array_reverse($createdRoads) as $id) apiRequest('DELETE', $baseUrl.'/api/roads/'.$id);
    foreach (array_reverse($createdAreas) as $id) apiRequest('DELETE', $baseUrl.'/api/areas/'.$id);
    foreach (array_reverse($createdNodes) as $id) apiRequest('DELETE', $baseUrl.'/api/nodes/'.$id);
}

if ($failures) {
    fwrite(STDERR, sprintf("%d/%d проверок навигации завершились ошибкой.\n", count($failures), $checks));
    exit(1);
}
fwrite(STDOUT, sprintf("OK: %d navigation API checks passed; test data removed.\n", $checks));
