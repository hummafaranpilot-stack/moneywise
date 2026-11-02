<?php
/** Shared postback helpers for postbacks-api.php and stats-api.php. */

// Affise {status} values; other networks are told to send status=1 for a sale.
const STATUS_NAMES = ['1' => 'approved', '2' => 'pending', '3' => 'declined', '5' => 'hold'];

/** The click a postback belongs to: clickid, or s2 (the id passed in our traffic link, e.g. ClickDealer). */
function postback_click_id($p) {
    if (($p['clickid'] ?? '') !== '') return (string) $p['clickid'];
    return (string) ($p['s2'] ?? '');
}

/** Latest postback per click id: [clickId => ['status' => ..., 'payout' => ..., 'ts' => ...]]. */
function conversions_by_click($postbacks) {
    $out = [];
    foreach ($postbacks as $pb) {
        $p = $pb['params'] ?? [];
        $cid = postback_click_id($p);
        if ($cid === '') continue;
        $out[$cid] = [
            'status' => STATUS_NAMES[(string) ($p['status'] ?? '')] ?? 'unknown',
            'payout' => is_numeric($p['payout'] ?? null) ? (float) $p['payout'] : 0.0,
            'ts'     => $pb['ts'],
        ];
    }
    return $out;
}
