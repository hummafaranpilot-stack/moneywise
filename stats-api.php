<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_login_api();

header('Content-Type: application/json');

function date_key($iso) { return substr($iso, 0, 10); }

$from = $_GET['from'] ?? '';
$to   = $_GET['to']   ?? '';

$clicks = db_read('clicks-log.json', []);
$filtered = array_filter($clicks, function ($c) use ($from, $to) {
    $d = date_key($c['ts']);
    if ($from && $d < $from) return false;
    if ($to && $d > $to) return false;
    return true;
});

$byOffer = [];
foreach ($filtered as $c) {
    $key = $c['offerId'] ?: '(unknown)';
    if (!isset($byOffer[$key])) $byOffer[$key] = ['offerId' => $key, 'total' => 0, 'valid' => 0, 'redirected' => 0];
    $byOffer[$key]['total']++;
    if (!empty($c['valid'])) $byOffer[$key]['valid']++;
    if (!empty($c['redirected'])) $byOffer[$key]['redirected']++;
}
$offers = array_values($byOffer);
usort($offers, fn($a, $b) => $b['total'] - $a['total']);

$totals = ['total' => 0, 'valid' => 0, 'redirected' => 0];
foreach ($offers as $o) {
    $totals['total'] += $o['total'];
    $totals['valid'] += $o['valid'];
    $totals['redirected'] += $o['redirected'];
}

echo json_encode(['offers' => $offers, 'totals' => $totals]);
