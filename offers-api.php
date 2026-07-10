<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_login_api();
no_cache_headers();

header('Content-Type: application/json');

function slugify($s) {
    $s = strtolower(trim($s));
    $s = preg_replace('/[^a-z0-9]+/', '_', $s);
    return trim($s, '_');
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $offers = db_read('offers.json', []);
    $offers = array_values(array_filter($offers, fn($o) => empty($o['deleted'])));
    echo json_encode($offers);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $body['_action'] ?? 'create';
$offers = db_read('offers.json', []);

if ($action === 'create') {
    if (empty($body['name'])) { http_response_code(400); echo json_encode(['error' => 'name required']); exit; }
    if (empty($body['link'])) { http_response_code(400); echo json_encode(['error' => 'link required']); exit; }
    $id = !empty($body['id']) ? slugify($body['id']) : slugify($body['name']);
    foreach ($offers as $o) {
        if ($o['id'] === $id) { http_response_code(409); echo json_encode(['error' => 'id already exists']); exit; }
    }
    $new = [
        'id' => $id,
        'link' => $body['link'],
        'name' => $body['name'],
        'network' => $body['network'] ?? '',
        'account' => $body['account'] ?? '',
        'accountEmail' => $body['accountEmail'] ?? '',
        'accountTelegram' => $body['accountTelegram'] ?? '',
        'offerTracker' => $body['offerTracker'] ?? '',
        'merchant' => $body['merchant'] ?? '',
        'tracker' => $body['tracker'] ?? '',
        'trackerEmail' => $body['trackerEmail'] ?? '',
        'payout' => $body['payout'] ?? '',
        'conversionRate' => $body['conversionRate'] ?? '',
        'internalName' => $body['internalName'] ?? '',
        'restrictions' => $body['restrictions'] ?? [],
        'color' => $body['color'] ?? '#64748b',
        'createdAt' => gmdate('c'),
    ];
    $offers[] = $new;
    db_write('offers.json', $offers);
    sync_offers_map();
    echo json_encode($new);
    exit;
}

if ($action === 'update') {
    $id = $body['id'] ?? '';
    foreach ($offers as &$o) {
        if ($o['id'] === $id) {
            foreach ($body as $k => $v) {
                if (in_array($k, ['id', '_action', 'createdAt'], true)) continue;
                $o[$k] = $v;
            }
            $o['updatedAt'] = gmdate('c');
            db_write('offers.json', $offers);
            sync_offers_map();
            echo json_encode($o);
            exit;
        }
    }
    unset($o);
    http_response_code(404);
    echo json_encode(['error' => 'not found']);
    exit;
}

if ($action === 'delete') {
    $id = $body['id'] ?? '';
    $offers = array_values(array_filter($offers, fn($o) => $o['id'] !== $id));
    db_write('offers.json', $offers);
    sync_offers_map();
    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'unknown action']);
