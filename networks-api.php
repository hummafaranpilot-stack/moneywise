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
                        'fbPage', 'trackerDomain', 'color'];

// Each network gets its own color (used on the Offers page cards). Picked from this
// list in order; once it runs out, further colors are spread around the color wheel.
const NETWORK_COLORS = ['#2563eb', '#16a34a', '#dc2626', '#d97706', '#7c3aed', '#0891b2', '#db2777',
                        '#65a30d', '#ea580c', '#4f46e5', '#0d9488', '#c026d3', '#b45309', '#0284c7',
                        '#be123c', '#15803d', '#9333ea', '#a16207', '#1d4ed8', '#e11d48', '#047857',
                        '#6d28d9', '#c2410c', '#475569'];

function hsl_hex($h, $s, $l) {
    $s /= 100; $l /= 100;
    $f = function ($n) use ($h, $s, $l) {
        $k = fmod($n + $h / 30, 12);
        $c = $l - $s * min($l, 1 - $l) * max(-1, min($k - 3, 9 - $k, 1));
        return str_pad(dechex((int) round($c * 255)), 2, '0', STR_PAD_LEFT);
    };
    return '#' . $f(0) . $f(8) . $f(4);
}

function unused_color($used) {
    foreach (NETWORK_COLORS as $c) if (!in_array($c, $used, true)) return $c;
    for ($i = 0; ; $i++) {
        $c = hsl_hex(fmod($i * 137.508, 360), 65, 42);
        if (!in_array($c, $used, true)) return $c;
    }
}

// Give every network that has no color yet one no other network uses. Returns true if any changed.
function assign_colors(&$networks) {
    $used = [];
    foreach ($networks as $n) if (!empty($n['color'])) $used[] = strtolower($n['color']);
    $changed = false;
    foreach ($networks as &$n) {
        if (!empty($n['color'])) continue;
        $n['color'] = unused_color($used);
        $used[] = $n['color'];
        $changed = true;
    }
    unset($n);
    return $changed;
}

function pick_fields($body) {
    $out = [];
    foreach (NETWORK_FIELDS as $k) {
        if (!array_key_exists($k, $body)) continue;
        $out[$k] = $k === 'enabled' ? (bool) $body[$k] : trim((string) $body[$k]);
    }
    // "Active but pending": pendingSince holds the YYYY-MM-DD it was set; only valid on an enabled network.
    if (isset($out['pendingSince']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $out['pendingSince'])) $out['pendingSince'] = '';
    if (($out['enabled'] ?? true) === false) $out['pendingSince'] = '';
    if (isset($out['color'])) {
        $out['color'] = strtolower($out['color']);
        if (!preg_match('/^#[0-9a-f]{6}$/', $out['color'])) unset($out['color']);
    }
    return $out;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $all = load_networks();
    if (assign_colors($all)) db_write(NETWORKS_FILE, $all);
    $networks = array_values(array_filter($all, fn($n) => empty($n['deleted'])));

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
    if (empty($new['color'])) $new['color'] = unused_color(array_map(fn($n) => strtolower($n['color'] ?? ''), $networks));
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
