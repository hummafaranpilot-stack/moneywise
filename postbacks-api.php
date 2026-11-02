<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/postbacks.php';
require_login_api();
no_cache_headers();

header('Content-Type: application/json');

function date_key($iso) { return substr($iso, 0, 10); }

$from = $_GET['from'] ?? '';
$to   = $_GET['to']   ?? '';

$postbacks = db_read('postbacks.json', []);

// Our own clicks by the id passed in the traffic link (s2), to show which click converted.
$clicksById = [];
foreach (db_read('clicks-log.json', []) as $c) {
    if (($c['s2'] ?? '') !== '') $clicksById[$c['s2']] = $c;
}

// A conversion fires a new postback every time its status changes (pending -> approved, ...).
// Collapse them by transaction id so each conversion counts once, with its latest status.
$conversions = [];
foreach ($postbacks as $i => $pb) {
    $p = $pb['params'] ?? [];
    $status = STATUS_NAMES[(string) ($p['status'] ?? '')] ?? null;
    $txid = $p['txid'] ?? '';
    if ($status === null && $txid === '') continue; // manual test hits, not real conversions
    $clickId = postback_click_id($p);
    $key = $txid !== '' ? 'tx:' . $txid : ($clickId !== '' ? 'click:' . $clickId : 'row:' . $i);
    $click = $clicksById[$clickId] ?? null;

    $conv = [
        'ts'        => $pb['ts'],
        'firstTs'   => $conversions[$key]['firstTs'] ?? $pb['ts'],
        'txid'      => $txid,
        'clickid'   => $clickId,
        'click'     => $click ? ['ts' => $click['ts'], 'offerId' => $click['offerId'], 'city' => $click['city'], 'region' => $click['region']] : null,
        'status'    => $status ?? 'unknown',
        'payout'    => is_numeric($p['payout'] ?? null) ? (float) $p['payout'] : 0.0,
        'currency'  => $p['currency'] ?? '',
        'offerId'   => $p['offer'] ?? ($click['offerId'] ?? ''),
        'offerName' => ($p['offer_name'] ?? '') !== '' ? $p['offer_name'] : ($p['offer'] ?? ($click['offerId'] ?? '(unknown)')),
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
