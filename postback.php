<?php
/**
 * Postback receiver — networks fire conversions here, e.g.
 * moneywise2026.com/postback.php?clickid={clickid}&payout={sum}&status={status}
 *
 * Public (no login — networks call it server-to-server). It only records what
 * it receives in data/postbacks.json and always answers "OK"; nothing else in
 * MoneyWise depends on it yet.
 */

require_once __DIR__ . '/includes/db.php';
no_cache_headers();

define('POSTBACKS_FILE', DATA_DIR . '/postbacks.json');
define('POSTBACKS_MAX', 5000); // keep only the newest entries so the file stays small

$record = [
    'ts'     => gmdate('c'),
    'ip'     => $_SERVER['REMOTE_ADDR'] ?? '',
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
    'params' => array_merge($_GET, $_POST),
];

// Lock while read-modify-writing — several postbacks can arrive at once.
$fh = @fopen(POSTBACKS_FILE, 'c+');
if ($fh && flock($fh, LOCK_EX)) {
    $all = json_decode(stream_get_contents($fh), true) ?: [];
    $all[] = $record;
    if (count($all) > POSTBACKS_MAX) $all = array_slice($all, -POSTBACKS_MAX);
    ftruncate($fh, 0);
    rewind($fh);
    fwrite($fh, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    fflush($fh);
    flock($fh, LOCK_UN);
}
if ($fh) fclose($fh);

header('Content-Type: text/plain');
echo 'OK';
