<?php
/** Shared postback helpers for postbacks-api.php and stats-api.php. */

// Affise {status} values; other networks are told to send status=1 for a sale.
const STATUS_NAMES = ['1' => 'approved', '2' => 'pending', '3' => 'declined', '5' => 'hold'];
// Networks that send the status as a word ({conv_status} etc.).
const STATUS_WORDS = ['approved' => 'approved', 'approve' => 'approved', 'confirmed' => 'approved', 'sale' => 'approved',
                      'pending' => 'pending', 'hold' => 'hold', 'on_hold' => 'hold',
                      'declined' => 'declined', 'rejected' => 'declined', 'trash' => 'declined', 'cancelled' => 'declined'];

/** Our status name for a postback's status value (1/2/3/5 or a word), or null if unknown. */
function status_name($raw) {
    $raw = strtolower(trim((string) $raw));
    return STATUS_NAMES[$raw] ?? STATUS_WORDS[$raw] ?? null;
}

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
            'status' => status_name($p['status'] ?? '') ?? 'unknown',
            'payout' => is_numeric($p['payout'] ?? null) ? (float) $p['payout'] : 0.0,
            'ts'     => $pb['ts'],
        ];
    }
    return $out;
}
