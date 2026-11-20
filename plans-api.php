<?php
/**
 * Weekly plans for the Plans page (plans.php).
 *
 * GET  → { today, currentWeek, plans: [...] }, newest week first.
 * POST _action create  { weekStart, name?, networks, offers }   weekStart is any date in the week
 *      _action update  { id, name?, networks?, offers? }        not for weeks that have ended
 *      _action apply   { id }                                   re-apply this week's plan now
 *      _action delete  { id }
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/plans.php';
require_login_api();
no_cache_headers();

header('Content-Type: application/json');

function fail($code, $msg) { http_response_code($code); echo json_encode(['error' => $msg]); exit; }

function clean_networks($in) {
    $out = [];
    foreach ((array) $in as $id => $state) {
        if (is_string($id) && $id !== '' && in_array($state, PLAN_STATES, true)) $out[$id] = $state;
    }
    return $out;
}

function clean_offers($in) {
    $out = [];
    foreach ((array) $in as $id => $star) {
        if (is_string($id) && $id !== '') $out[$id] = (bool) $star;
    }
    return $out;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    apply_due_plans();
    $plans = plans_read();
    usort($plans, fn($a, $b) => strcmp($b['weekStart'] ?? '', $a['weekStart'] ?? ''));
    echo json_encode(['today' => plan_today(), 'currentWeek' => week_monday(plan_today()), 'plans' => $plans]);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $body['_action'] ?? '';
$plans = plans_read();
$currentWeek = week_monday(plan_today());

if ($action === 'create') {
    $monday = week_monday((string) ($body['weekStart'] ?? ''));
    if (!$monday) fail(400, 'pick a valid week');
    if ($monday < $currentWeek) fail(400, 'that week has already ended');
    foreach ($plans as $p) if (($p['weekStart'] ?? '') === $monday) fail(409, 'a plan for that week already exists');
    $name = trim((string) ($body['name'] ?? ''));
    $plan = [
        'id'        => 'plan_' . str_replace('-', '', $monday) . '_' . substr(bin2hex(random_bytes(2)), 0, 4),
        'name'      => $name !== '' ? $name : 'Plan ' . (count($plans) + 1),
        'weekStart' => $monday,
        'weekEnd'   => week_sunday($monday),
        'networks'  => clean_networks($body['networks'] ?? []),
        'offers'    => clean_offers($body['offers'] ?? []),
        'appliedAt' => null,
        'createdAt' => gmdate('c'),
    ];
    $plans[] = $plan;
    plans_write($plans);
    apply_due_plans(); // a plan made for the current week takes effect right away
    foreach (plans_read() as $p) if ($p['id'] === $plan['id']) { echo json_encode($p); exit; }
    exit;
}

$id = (string) ($body['id'] ?? '');
$idx = null;
foreach ($plans as $i => $p) if (($p['id'] ?? '') === $id) { $idx = $i; break; }
if ($idx === null) fail(404, 'plan not found');

if ($action === 'update') {
    if ($plans[$idx]['weekStart'] < $currentWeek) fail(400, 'this week has ended; past plans are kept as history');
    if (array_key_exists('name', $body) && trim((string) $body['name']) !== '') $plans[$idx]['name'] = trim((string) $body['name']);
    if (array_key_exists('networks', $body)) $plans[$idx]['networks'] = clean_networks($body['networks']);
    if (array_key_exists('offers', $body)) $plans[$idx]['offers'] = clean_offers($body['offers']);
    $plans[$idx]['updatedAt'] = gmdate('c');
    plans_write($plans);
    echo json_encode($plans[$idx]);
    exit;
}

if ($action === 'apply') {
    if ($plans[$idx]['weekStart'] !== $currentWeek) fail(400, 'only this week\'s plan can be applied now');
    apply_plan($plans[$idx]);
    plans_write($plans);
    echo json_encode($plans[$idx]);
    exit;
}

if ($action === 'delete') {
    array_splice($plans, $idx, 1);
    plans_write($plans);
    echo json_encode(['ok' => true]);
    exit;
}

fail(400, 'unknown action');
