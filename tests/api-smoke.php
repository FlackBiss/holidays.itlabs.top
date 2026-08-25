<?php

declare(strict_types=1);

/**
 * End-to-end smoke test for a populated holidays backend.
 * Usage: php tests/api-smoke.php https://holidays.itlabs.top
 */

$baseUrl = rtrim($argv[1] ?? 'http://holidays.local', '/');
$checks = 0;
$failures = [];

function request(string $method, string $url, ?array $json = null, ?string $accept = null): array
{
    $handle = curl_init($url);
    $headers = ['Accept: '.($accept ?? 'application/json')];
    if ($json !== null) {
        $headers[] = 'Content-Type: '.($method === 'PATCH' ? 'application/merge-patch+json' : 'application/json');
        curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($json, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }
    curl_setopt_array($handle, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
    ]);
    $raw = curl_exec($handle);
    if ($raw === false) {
        throw new RuntimeException(curl_error($handle));
    }
    $status = curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
    $headerSize = curl_getinfo($handle, CURLINFO_HEADER_SIZE);
    $contentType = (string) curl_getinfo($handle, CURLINFO_CONTENT_TYPE);
    curl_close($handle);

    return [
        'status' => $status,
        'contentType' => $contentType,
        'headers' => substr($raw, 0, $headerSize),
        'body' => substr($raw, $headerSize),
    ];
}

function orderedByPriority(array $items): bool
{
    $previous = PHP_INT_MIN;
    foreach ($items as $item) {
        $priority = (int) ($item['priority'] ?? 0);
        if ($priority < $previous) return false;
        $previous = $priority;
    }
    return true;
}

function jsonBody(array $response): array
{
    $decoded = json_decode($response['body'], true);
    return is_array($decoded) ? $decoded : [];
}

function members(array $payload): array
{
    return array_is_list($payload) ? $payload : ($payload['member'] ?? $payload['hydra:member'] ?? []);
}

function check(bool $condition, string $message): void
{
    global $checks, $failures;
    ++$checks;
    if (!$condition) {
        $failures[] = $message;
        fwrite(STDERR, "FAIL: {$message}\n");
    }
}

function expectStatus(array $response, int|array $expected, string $name): void
{
    $expected = (array) $expected;
    check(in_array($response['status'], $expected, true), sprintf(
        '%s: HTTP %d instead of %s; body: %s',
        $name,
        $response['status'],
        implode('/', $expected),
        mb_substr(trim($response['body']), 0, 300),
    ));
}

try {
    expectStatus(request('GET', $baseUrl.'/api/docs.jsonopenapi', null, 'application/vnd.openapi+json'), 200, 'OpenAPI schema');

    $homeResponse = request('GET', $baseUrl.'/api/home');
    expectStatus($homeResponse, 200, 'Home');
    $home = jsonBody($homeResponse);
    check(is_array($home['weather'] ?? null) && isset($home['weather']['provider']), 'Home embeds weather data');
    check(is_string($home['settings']['logo'] ?? null) && $home['settings']['logo'] !== '', 'Home embeds logo URL');
    check(count($home['standby'] ?? []) > 0 && !empty($home['standby'][0]['url']), 'Standby contains uploaded media');
    check(orderedByPriority($home['standby'] ?? []), 'Standby is ordered by backend priority');
    check(!empty($home['serverDateTime']['iso']) && ($home['serverDateTime']['timezone'] ?? null) === 'Europe/Moscow', 'Home contains server date and time');

    $weather = request('GET', $baseUrl.'/api/weather/current');
    expectStatus($weather, 200, 'Weather');
    check((jsonBody($weather)['location'] ?? null) === 'Аксаково', 'Weather location is Aksakovo');

    $openApi = jsonBody(request('GET', $baseUrl.'/api/docs.jsonopenapi?audit='.time()));
    foreach (['/api/map/search', '/api/map/categories', '/api/navigation/qr/{placeId}', '/api/terminals/{code}/ping'] as $documentedPath) {
        check(isset($openApi['paths'][$documentedPath]), 'OpenAPI documents '.$documentedPath);
    }

    $sectionSlugs = [
        'about', 'guest-info', 'service-hours', 'meal-times', 'connect', 'transfer',
        'public-transport', 'news', 'animation', 'infrastructure', 'medical-center', 'gallery', 'prices',
        'residence-rules', 'uav-alert', 'taganrog',
    ];
    $catalogResponse = request('GET', $baseUrl.'/api/sections');
    expectStatus($catalogResponse, 200, 'Section catalog');
    check(count(members(jsonBody($catalogResponse))) === count($sectionSlugs), 'Section catalog contains all fixed screens');
    $sectionData = [];
    foreach ($sectionSlugs as $slug) {
        $sectionResponse = request('GET', $baseUrl.'/api/sections/'.$slug);
        expectStatus($sectionResponse, 200, 'Section '.$slug);
        $section = jsonBody($sectionResponse);
        $sectionData[$slug] = $section['data'] ?? [];
        check(($section['slug'] ?? null) === $slug, 'Section slug '.$slug);
        check(array_key_exists('documents', $section['data'] ?? []), 'Section '.$slug.' supports generic documents');
        check(count($section['data'] ?? []) >= 1, 'Section '.$slug.' returns data');
    }

    check(!empty($sectionData['about']['page']), 'About page is populated');
    check(!empty($sectionData['guest-info']['page']) && count($sectionData['guest-info']['documents'] ?? []) >= 7, 'Guest page and named documents are populated');
    check(array_reduce($sectionData['guest-info']['documents'] ?? [], static fn (bool $ok, array $document): bool => $ok && !array_key_exists('description', $document) && !array_key_exists('externalUrl', $document) && str_ends_with($document['url'] ?? '', '.pdf'), true), 'Guest document API contains title and ordered PDF without descriptions or external links');
    check(!empty($sectionData['service-hours']['page']['documentUrl']) && !empty($sectionData['service-hours']['page']['data']['receptionDescription']) && count($sectionData['service-hours']['page']['serviceQrLinks'] ?? []) >= 3, 'Service PDF page and ordered QR collection are populated');
    check(($sectionData['service-hours']['page']['title'] ?? null) === 'Контакты', 'Contacts section uses the approved public title');
    check(array_reduce($sectionData['service-hours']['page']['serviceQrLinks'] ?? [], static fn (bool $ok, array $item): bool => $ok && !empty($item['url']), true), 'Every service QR item has an uploaded image');
    check(orderedByPriority($sectionData['service-hours']['page']['serviceQrLinks'] ?? []), 'Service QR collection is ordered by backend priority');
    $mealPage = $sectionData['meal-times']['page'] ?? [];
    check(!empty($mealPage['hedgehogUrl']) && !empty($mealPage['diningHalls']['imageUrl']) && !empty($mealPage['cafe']['imageUrl']) && !empty($mealPage['phytoBar']['imageUrl']), 'Meal times contains editable hedgehog and three section images');
    check(!empty($mealPage['diningHalls']['mainTerritory']['description']) && !empty($mealPage['diningHalls']['buildingSevenTerritory']['description']) && !empty($mealPage['cafe']['description']) && !empty($mealPage['phytoBar']['description']), 'Meal times contains separate editable schedules for both dining halls, cafe and phyto bar');
    $connectPage = $sectionData['connect']['page'] ?? [];
    check(!empty($connectPage['imageUrl']) && !empty($connectPage['logoUrl']), 'Connect page contains image and logo instead of PDF');
    check(count($connectPage['prizeBenefits'] ?? []) > 0 && count($connectPage['importantNotices'] ?? []) > 0, 'Connect page contains both editable text lists');
    check(count($connectPage['rewards'] ?? []) > 0 && array_reduce($connectPage['rewards'], static fn (bool $ok, array $reward): bool => $ok && !empty($reward['achievement']) && ($reward['points'] ?? 0) >= 1 && ($reward['points'] ?? 0) <= 5, true), 'Connect reward points are limited to 1-5');
    $transferPage = $sectionData['transfer']['page'] ?? [];
    check(!empty($transferPage['mapUrl']) && count($transferPage['mainTerritoryDepartureTimes'] ?? []) > 0 && count($transferPage['buildingSevenDepartureTimes'] ?? []) > 0, 'Transfer contains a static map and two editable departure lists');
    $transportRoutes = $sectionData['public-transport']['routes'] ?? [];
    check(array_column($transportRoutes, 'routeNumber') === ['271', '70'] && array_reduce($transportRoutes, static fn (bool $ok, array $route): bool => $ok && !empty($route['routeMapUrl']) && count($route['schedules'] ?? []) > 0, true), 'Public transport contains routes 271 and 70 with uploaded maps and schedules');
    check(array_reduce($transportRoutes, static fn (bool $ok, array $route): bool => $ok && array_reduce($route['schedules'] ?? [], static fn (bool $scheduleOk, array $schedule): bool => $scheduleOk && !empty($schedule['stopName']) && !empty($schedule['days']) && count($schedule['times'] ?? []) > 0, true), true), 'Every public transport schedule contains stop, days and departure times');
    check(orderedByPriority($transportRoutes), 'Public transport routes are ordered by backend priority');
    check(count($sectionData['public-transport']['documents'] ?? []) === 0 && count($sectionData['transfer']['documents'] ?? []) === 0, 'Transport sections no longer expose obsolete generic documents');
    check(count($sectionData['news']['posters'] ?? []) > 0, 'News posters are populated');
    check(count($sectionData['animation']['posters'] ?? []) > 0, 'Animation posters are populated');
    $infrastructurePage = $sectionData['infrastructure']['page'] ?? [];
    check(count($infrastructurePage['mainTerritory'] ?? []) > 0 && count($infrastructurePage['buildingSevenTerritory'] ?? []) > 0, 'Infrastructure has two editable territory lists');
    check(count($sectionData['infrastructure']['documents'] ?? []) === 0, 'Infrastructure no longer uses the obsolete uploaded file');
    check(count($sectionData['medical-center']['departments'] ?? []) > 0 && count($sectionData['medical-center']['departments'][0]['items'] ?? []) > 0, 'Medical service sections and cards are populated');
    check(!empty($sectionData['medical-center']['page']['imageUrl']) && !empty($sectionData['medical-center']['page']['mascotOneUrl']) && !empty($sectionData['medical-center']['page']['mascotTwoUrl']), 'Medical center page contains one page image and two mascots');
    check(!empty($sectionData['medical-center']['departments'][0]['items'][0]['url']) && !empty($sectionData['medical-center']['departments'][0]['items'][0]['fileUrl']), 'Medical cards contain link and one image');
    check(array_reduce($sectionData['medical-center']['departments'] ?? [], static fn (bool $ok, array $department): bool => $ok && array_reduce($department['items'] ?? [], static fn (bool $itemsOk, array $service): bool => $itemsOk && !empty($service['description']), true), true), 'Every medical service contains an editable description');
    check(count($sectionData['gallery']['media'] ?? []) > 0, 'Gallery is populated');
    check(orderedByPriority($sectionData['news']['posters'] ?? []) && orderedByPriority($sectionData['animation']['posters'] ?? []) && orderedByPriority($sectionData['gallery']['media'] ?? []), 'Poster and gallery collections are ordered by backend priority');
    check(count($sectionData['prices']['categories'] ?? []) > 0, 'Structured price categories are populated');
    check(array_reduce($sectionData['prices']['categories'] ?? [], static fn (bool $ok, array $price): bool => $ok && !array_key_exists('description', $price) && !array_key_exists('externalUrl', $price) && str_ends_with($price['url'] ?? '', '.pdf'), true), 'Price API contains ordered PDF files without descriptions or external links');
    $rulesPage = $sectionData['residence-rules']['page'] ?? [];
    check(!empty($rulesPage['fullRulesQrUrl']) && !empty($rulesPage['damageCompensationQrUrl']) && !empty($rulesPage['placementIconUrl']) && !empty($rulesPage['visitorPassIconUrl']) && !empty($rulesPage['medicalProceduresIconUrl']), 'Residence rules contain two QR codes and three icons');
    check(!empty($rulesPage['checkInTime']) && !empty($rulesPage['checkOutTime']) && count($rulesPage['placementRules'] ?? []) > 0 && count($rulesPage['safetyRules'] ?? []) > 0 && count($rulesPage['medicalProcedureRules'] ?? []) > 0 && !empty($rulesPage['visitorPassText']), 'Residence rules structured text is populated');
    check(count($sectionData['uav-alert']['memos'] ?? []) > 0, 'UAV memos are populated');
    $taganrogPage = $sectionData['taganrog']['page'] ?? [];
    check(!empty($taganrogPage['siteQrUrl']) && !empty($taganrogPage['logoUrl']) && count($taganrogPage['images'] ?? []) === 3, 'Taganrog contains QR, logo and three images');
    check(!empty($taganrogPage['mission']) && !empty($taganrogPage['description']) && count($taganrogPage['aboutSanatorium'] ?? []) > 0, 'Taganrog editable text content is populated');

    foreach ([
        'content_items', 'content_pages', 'kiosk_terminals', 'map_edges', 'map_nodes', 'map_places',
        'map_plans', 'room_categories', 'section_documents', 'site_settings', 'standby_media',
        'news_posters', 'animation_posters', 'gallery_media', 'public_transport_routes',
    ] as $collection) {
        $response = request('GET', $baseUrl.'/api/'.$collection);
        expectStatus($response, 200, 'Collection '.$collection);
        $items = members(jsonBody($response));
        check(count($items) > 0, 'Collection '.$collection.' is populated');
        if (!empty($items[0]['id'])) {
            expectStatus(request('GET', $baseUrl.'/api/'.$collection.'/'.$items[0]['id']), 200, 'Item '.$collection);
        }
    }

    $priceDocumentsResponse = request('GET', $baseUrl.'/api/section_documents?section=prices');
    expectStatus($priceDocumentsResponse, 200, 'Price document filter');
    $priceDocuments = members(jsonBody($priceDocumentsResponse));
    check(count($priceDocuments) >= 6, 'Price documents contain seeded categories and allow adding new ones');

    $prices = jsonBody(request('GET', $baseUrl.'/api/sections/prices'));
    $priceRoots = $prices['data']['documents'] ?? [];
    check(count($priceRoots) >= 6, 'Prices have seeded categories from the approved layout');
    check(array_reduce($priceRoots, static fn (bool $ok, array $category): bool => $ok && !empty($category['url']) && empty($category['items']), true), 'Every price category directly contains one file');

    $rules = jsonBody(request('GET', $baseUrl.'/api/sections/residence-rules'));
    check(!empty($rules['data']['page']) && empty($rules['data']['page']['documentUrl']), 'Residence rules are served as one structured page without PDF');

    $placesResponse = request('GET', $baseUrl.'/api/map_places');
    $places = members(jsonBody($placesResponse));
    $residential = array_values(array_filter($places, static fn (array $place): bool => ($place['type'] ?? null) === 'residential'));
    $infrastructure = array_values(array_filter($places, static fn (array $place): bool => ($place['type'] ?? null) === 'infrastructure'));
    check(count($residential) >= 2, 'Residential buildings are populated');
    check(array_reduce($residential, static fn (bool $ok, array $place): bool => $ok && !empty($place['buildingNumber']), true), 'Only residential objects use numbered buildings');
    check(array_reduce($infrastructure, static fn (bool $ok, array $place): bool => $ok && empty($place['buildingNumber']), true), 'Infrastructure objects are not numbered buildings');
    check(array_reduce($places, static fn (bool $ok, array $place): bool => $ok && count($place['photos'] ?? []) > 0 && !empty($place['photos'][0]['url']), true), 'Every map object has an ordered photo slider');
    check(orderedByPriority($places), 'Map objects are ordered by backend priority');
    $residentialPlaces = array_values(array_filter($places, static fn (array $place): bool => ($place['type'] ?? null) === 'residential'));
    check(array_reduce($residentialPlaces, static function (bool $ok, array $place): bool {
        if (!$ok || empty($place['roomCategories'])) return false;
        foreach ($place['roomCategories'] as $category) {
            if (empty($category['title']) || empty($category['photos'][0]['url'])) return false;
        }
        return true;
    }, true), 'Residential buildings use reusable room categories with photo sliders');
    check(array_reduce($places, static fn (bool $ok, array $place): bool => $ok && !empty($place['icon']['url']), true), 'Every map object has its own uploaded map icon');
    check(array_reduce($places, static fn (bool $ok, array $place): bool => $ok && in_array($place['category'] ?? null, ['other', 'sport', 'recreation', 'residential', 'buildings'], true), true), 'Every map object belongs to a fixed legend category');
    check(array_reduce($infrastructure, static fn (bool $ok, array $place): bool => $ok && array_key_exists('routeDrawn', $place) && array_key_exists('routeAvailable', $place), true), 'Every infrastructure object exposes route drawing availability');
    $legendCategories = jsonBody(request('GET', $baseUrl.'/api/map/categories'));
    check(array_column($legendCategories, 'name') === ['Прочее', 'Спорт', 'Места отдыха', 'Жилые корпуса', 'Здания'], 'Map legend exposes all five fixed object categories');

    foreach (['areas', 'points', 'nodes', 'roads', 'objects', 'terminals'] as $editorCollection) {
        expectStatus(request('GET', $baseUrl.'/api/'.$editorCollection), 200, 'Navigation editor collection '.$editorCollection);
    }

    $plans = members(jsonBody(request('GET', $baseUrl.'/api/map_plans')));
    $planId = $plans[0]['id'] ?? null;
    if (is_int($planId)) {
        $pointAResponse = request('POST', $baseUrl.'/api/points', ['planId' => $planId, 'name' => 'smoke-a', 'x' => 15.5, 'y' => 25.5, 'latitude' => 56.034, 'longitude' => 37.602]);
        expectStatus($pointAResponse, 201, 'Create navigation point A');
        $pointA = jsonBody($pointAResponse)['id'] ?? null;
        $pointBResponse = request('POST', $baseUrl.'/api/nodes', ['planId' => $planId, 'name' => 'smoke-b', 'x' => 35.5, 'y' => 45.5, 'latitude' => 56.0341, 'longitude' => 37.6021]);
        expectStatus($pointBResponse, 201, 'Create navigation node B');
        $pointB = jsonBody($pointBResponse)['id'] ?? null;
        if (is_int($pointA) && is_int($pointB)) {
            expectStatus(request('PATCH', $baseUrl.'/api/points/'.$pointA, ['x' => 16.5]), 200, 'Update navigation point');
            $roadResponse = request('POST', $baseUrl.'/api/roads', ['planId' => $planId, 'fromNodeId' => $pointA, 'toNodeId' => $pointB, 'bidirectional' => true]);
            expectStatus($roadResponse, 201, 'Create navigation road');
            $roadId = jsonBody($roadResponse)['id'] ?? null;
            if (is_int($roadId)) {
                expectStatus(request('PATCH', $baseUrl.'/api/roads/'.$roadId, ['accessible' => false]), 200, 'Update navigation road');
                expectStatus(request('DELETE', $baseUrl.'/api/roads/'.$roadId), 204, 'Delete navigation road');
            }
            $areaResponse = request('POST', $baseUrl.'/api/areas', ['planId' => $planId, 'title' => 'smoke-area', 'points' => [['x' => 1, 'y' => 1], ['x' => 100, 'y' => 1], ['x' => 100, 'y' => 100]]]);
            expectStatus($areaResponse, 201, 'Create navigation area');
            $areaId = jsonBody($areaResponse)['id'] ?? null;
            if (is_int($areaId)) {
                expectStatus(request('PATCH', $baseUrl.'/api/areas/'.$areaId, ['title' => 'smoke-area-updated']), 200, 'Update navigation area');
                expectStatus(request('DELETE', $baseUrl.'/api/areas/'.$areaId), 204, 'Delete navigation area');
            }
            expectStatus(request('DELETE', $baseUrl.'/api/points/'.$pointA), 204, 'Delete navigation point A');
            expectStatus(request('DELETE', $baseUrl.'/api/nodes/'.$pointB), 204, 'Delete navigation node B');
        }
    }

    $firstPlace = $places[0] ?? [];
    $placementNode = $firstPlace['node']['id'] ?? null;
    $placementArea = $firstPlace['area']['id'] ?? null;
    if (is_int($firstPlace['id'] ?? null) && is_int($placementNode)) {
        expectStatus(request('PATCH', $baseUrl.'/api/objects/'.$firstPlace['id'], ['nodeId' => $placementNode, 'areaId' => $placementArea]), 200, 'Update object map placement');
    }
    $terminals = members(jsonBody(request('GET', $baseUrl.'/api/terminals')));
    $firstTerminal = $terminals[0] ?? [];
    if (is_int($firstTerminal['id'] ?? null) && is_int($firstTerminal['startNodeId'] ?? null)) {
        expectStatus(request('PATCH', $baseUrl.'/api/terminals/'.$firstTerminal['id'], ['nodeId' => $firstTerminal['startNodeId'], 'areaId' => $firstTerminal['areaId'] ?? null]), 200, 'Update terminal map placement');
    }

    $search = request('GET', $baseUrl.'/api/map/search?q='.rawurlencode('мед'));
    expectStatus($search, 200, 'Map search');
    check((jsonBody($search)['total'] ?? 0) > 0, 'Map search finds medical center without Meilisearch');

    $destination = $places[0]['id'] ?? null;
    check(is_int($destination), 'Navigation destination exists');
    if (is_int($destination)) {
        $terminalRoute = request('POST', $baseUrl.'/api/navigation/routes', [
            'destinationPlaceId' => $destination,
            'terminalCode' => 'main-kiosk',
        ]);
        expectStatus($terminalRoute, 201, 'Terminal navigation');
        $terminalRouteData = jsonBody($terminalRoute);
        check(count($terminalRouteData['points'] ?? []) >= 1, 'Terminal navigation returns route points');
        check(str_contains((string) ($terminalRouteData['mobileUrl'] ?? ''), 'destination='), 'Navigation returns transferable mobile URL');

        $gpsRoute = request('POST', $baseUrl.'/api/navigation/routes', [
            'destinationPlaceId' => $destination,
            'latitude' => 56.03395,
            'longitude' => 37.60190,
        ]);
        expectStatus($gpsRoute, 201, 'Phone GPS navigation');
        $gps = jsonBody($gpsRoute);
        check(is_array($gps['sourcePosition'] ?? null), 'GPS route preserves actual phone position');
        check(is_array($gps['snappedPosition'] ?? null), 'GPS route snaps to nearest graph node');
        check(is_numeric($gps['snapDistanceMeters'] ?? null), 'GPS route reports snap distance');

        $accessibleRoute = request('POST', $baseUrl.'/api/navigation/routes', [
            'destinationPlaceId' => $destination,
            'terminalCode' => 'main-kiosk',
            'accessible' => true,
        ]);
        expectStatus($accessibleRoute, 201, 'Accessible navigation');

        $qr = request('GET', $baseUrl.'/api/navigation/qr/'.$destination);
        expectStatus($qr, 200, 'Navigation QR');
        check(str_starts_with($qr['contentType'], 'image/png'), 'Navigation QR is PNG');
    }

    expectStatus(request('POST', $baseUrl.'/api/terminals/main-kiosk/ping'), 200, 'Terminal ping');
    expectStatus(request('GET', $baseUrl.'/api/sections/not-a-section'), 404, 'Unknown section');
    expectStatus(request('GET', $baseUrl.'/api/map/search?q=x'), 400, 'Short map search rejected');
    expectStatus(request('POST', $baseUrl.'/api/navigation/routes', ['destinationPlaceId' => 0]), [400, 422], 'Invalid navigation rejected');

    $mediaUrl = $home['settings']['logo'] ?? null;
    if (is_string($mediaUrl)) {
        expectStatus(request('GET', $baseUrl.$mediaUrl), 200, 'Uploaded logo file');
    }
} catch (Throwable $error) {
    $failures[] = $error::class.': '.$error->getMessage();
    fwrite(STDERR, 'ERROR: '.end($failures)."\n");
}

if ($failures !== []) {
    fwrite(STDERR, sprintf("\n%d/%d checks failed.\n", count($failures), $checks));
    exit(1);
}

fwrite(STDOUT, sprintf("OK: %d API checks passed against %s\n", $checks, $baseUrl));
