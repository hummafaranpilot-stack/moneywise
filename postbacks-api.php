<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_login_api();
no_cache_headers();

header('Content-Type: application/json');

// Affise {status} values.
const STATUS_NAMES = ['1' => 'approved', '2' => 'pending', '3' => 'declined', '5' => 'hold'];

function date_key($iso) { return substr($iso, 0, 10); }

$from = $_GET['from'] ?? '';
$to   = $_GET['to']   ?? '';

$postbacks = db_read('postbacks.json', []);

// A conversion fires a new postback every time its status changes (pending -> approved, ...).
// Collapse them by transaction id so each conversion counts once, with its latest status.
$conversions = [];
foreach ($postbacks as $i => $pb) {
    $p = $pb['params'] ?? [];
    $status = STATUS_NAMES[(string) ($p['status'] ?? '')] ?? null;
    $txid = $p['txid'] ?? '';
    if ($status === null && $txid === '') continue; // manual test hits, not real conversions
    $key = $txid !== '' ? 'tx:' . $txid : (($p['clickid'] ?? '') !== '' ? 'click:' . $p['clickid'] : 'row:' . $i);

    $conv = [
        'ts'        => $pb['ts'],
        'firstTs'   => $conversions[$key]['firstTs'] ?? $pb['ts'],
        'txid'      => $txid,
        'clickid'   => $p['clickid'] ?? '',
        'status'    => $status ?? 'unknown',
        'payout'    => is_numeric($p['payout'] ?? null) ? (float) $p['payout'] : 0.0,
        'currency'  => $p['currency'] ?? '',
        'offerId'   => $p['offer'] ?? '',
        'offerName' => ($p['offer_name'] ?? '') !== '' ? $p['offer_name'] : ($p['offer'] ?? '(unknown)'),
        'goal'      => $p['goal'] ?? '',
        'sub1'      => $p['sub1'] ?? '',
        'geo'       => $p['geo'] ?? '',
        'updates'   => ($conversions[$key]['updates'] ?? 0) + 1,
    ];
    $conversions[$key] = $conv; // file is chronological, so the last one wins
}

$filtered = array_filter($conversions, function ($c) use ($from, $to) {
    $d = date_key($c['firstTs']);
    if ($from && $d < $from) return false;
    if ($to && $d > $to) return false;
    return true;
});

$emptyCounts = ['total' => 0, 'approved' => 0, 'pending' => 0, 'hold' => 0, 'declined' => 0, 'unknown' => 0, 'approvedPayout' => 0.0, 'pendingPayout' => 0.0];

function count_conversion(&$bucket, $c) {
    $bucket['total']++;
    $bucket[$c['status']]++;
    if ($c['status'] === 'approved') $bucket['approvedPayout'] += $c['payout'];
    if ($c['status'] === 'pending' || $c['status'] === 'hold') $bucket['pendingPayout'] += $c['payout'];
}

$byOffer = [];
$totals = $emptyCounts;
foreach ($filtered as $c) {
    $key = $c['offerName'];
    if (!isset($byOffer[$key])) $byOffer[$key] = ['offerName' => $key] + $emptyCounts;
    count_conversion($byOffer[$key], $c);
    count_conversion($totals, $c);
}
$offers = array_values($byOffer);
usort($offers, fn($a, $b) => $b['total'] - $a['total']);

$recent = array_values($filtered);
usort($recent, fn($a, $b) => strcmp($b['ts'], $a['ts']));
$recent = array_slice($recent, 0, 200);

echo json_encode(['offers' => $offers, 'totals' => $totals, 'conversions' => $recent]);
