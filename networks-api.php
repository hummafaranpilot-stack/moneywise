<?php
/**
 * Networks list, managed from the Networks page (networks.php).
 *
 * Edits are saved to data/networks-live.json, which is gitignored so a
 * deploy never overwrites what the admin changed on the live site. The
 * tracked data/networks.json is only the starting point: it is copied
 * into networks-live.json the first time that file doesn't exist yet.
 *
 * GET returns a flat JSON array, one object per network. A downstream sync
 * client reads name, identity, paymentTerms, minPayout and status from it;
 * status is combined with the enabled flag, e.g. "approved, enabled". The
 * offers page dropdown also uses the remaining fields (id, email, telegram,
 * trackers). GET ?raw=1 returns the stored records unchanged (for the
 * Networks page). POST with _action create / update / delete edits the list.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_login_api();
no_cache_headers();

header('Content-Type: application/json');

define('NETWORKS_FILE', 'networks-live.json');

function load_networks() {
    if (file_exists(DATA_DIR . '/' . NETWORKS_FILE)) return db_read(NETWORKS_FILE, []);
    return db_read('networks.json', []);
}

function slugify($s) {
    $s = strtolower(trim($s));
    $s = preg_replace('/[^a-z0-9]+/', '_', $s);
    return trim($s, '_');
}

// Only these fields can be set from the Networks page.
const NETWORK_FIELDS = ['name', 'identity', 'email', 'telegram', 'ourTracker', 'ownerTracker', 'status',
                        'paymentTerms', 'minPayout', 'paymentMethods', 'manager', 'notes', 'enabled', 'pendingSince',
                        'fbPage', 'trackerDomain'];

function pick_fields($body) {
    $out = [];
    foreach (NETWORK_FIELDS as $k) {
        if (!array_key_exists($k, $body)) continue;
        $out[$k] = $k === 'enabled' ? (bool) $body[$k] : trim((string) $body[$k]);
    }
    // "Active but pending": pendingSince holds the YYYY-MM-DD it was set; only valid on an enabled network.
    if (isset($out['pendingSince']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $out['pendingSince'])) $out['pendingSince'] = '';
    if (($out['enabled'] ?? true) === false) $out['pendingSince'] = '';
    return $out;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $networks = array_values(array_filter(load_networks(), fn($n) => empty($n['deleted'])));

    if (!empty($_GET['raw'])) {
        echo json_encode($networks);
        exit;
    }

    echo json_encode(array_map(function ($n) {
        // A network without an "enabled" flag was never disabled.
        $enabled = ($n['enabled'] ?? true) !== false;
        return [
            'name'         => $n['name'] ?? '',
            'identity'     => $n['identity'] ?? '',
            'paymentTerms' => $n['paymentTerms'] ?? '',
            'minPayout'    => $n['minPayout'] ?? '',
            'status'       => trim(($n['status'] ?? '') . ', ' . ($enabled ? 'enabled' : 'disabled'), ', '),
        ] + $n;
    }, $networks));
    exit;
}

$body = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $body['_action'] ?? '';
$networks = load_networks();

if ($action === 'create') {
    $fields = pick_fields($body);
    if (($fields['name'] ?? '') === '') { http_response_code(400); echo json_encode(['error' => 'name required']); exit; }
    $new = $fields + ['identity' => '', 'email' => '', 'telegram' => '', 'ourTracker' => '', 'ownerTracker' => '',
                      'status' => 'approved', 'paymentTerms' => '', 'minPayout' => '', 'paymentMethods' => '',
                      'manager' => '', 'notes' => '', 'enabled' => true,
                      'fbPage' => '', 'trackerDomain' => ''];
    $new['id'] = slugify($new['name'] . ' ' . $new['identity']) . '-' . substr(bin2hex(random_bytes(2)), 0, 4);
    $new['createdAt'] = gmdate('c');
    $networks[] = $new;
    db_write(NETWORKS_FILE, $networks);
    echo json_encode($new);
    exit;
}

if ($action === 'update') {
    $id = $body['id'] ?? '';
    foreach ($networks as &$n) {
        if (($n['id'] ?? '') === $id) {
            $fields = pick_fields($body);
            if (array_key_exists('name', $fields) && $fields['name'] === '') {
                http_response_code(400); echo json_encode(['error' => 'name required']); exit;
            }
            $n = array_merge($n, $fields);
            $n['updatedAt'] = gmdate('c');
            db_write(NETWORKS_FILE, $networks);
            echo json_encode($n);
            exit;
        }
    }
    unset($n);
    http_response_code(404);
    echo json_encode(['error' => 'not found']);
    exit;
}

if ($action === 'delete') {
    $id = $body['id'] ?? '';
    $networks = array_values(array_filter($networks, fn($n) => ($n['id'] ?? '') !== $id));
    db_write(NETWORKS_FILE, $networks);
    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'unknown action']);
