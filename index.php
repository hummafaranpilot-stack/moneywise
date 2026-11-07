<?php
/**
 * MoneyWise redirect service (PHP — runs on standard shared hosting, no Node needed).
 *
 * moneywise2026.com/{offerId}?any=params  →  validates visitor IP is US,
 * shows a blank page, and immediately forwards to the mapped offer's
 * real tracking link with all incoming query params merged in.
 * Non-US (or unresolvable) IPs are rejected — never redirected.
 */

require_once __DIR__ . '/includes/db.php';

function client_ip() {
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($parts[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? '';
}

// Sticky-session proxies (NodeMaven/IPRoyal) often reuse the same exit IP
// across many clicks in a short window — cache geo results per IP for 5 min
// so those hits skip the slow external ip-api.com round-trip entirely.
define('GEO_CACHE_FILE', __DIR__ . '/data/geo-cache.json');
define('GEO_CACHE_TTL', 300); // seconds

function geo_cache_get($ip) {
    $all = db_read('geo-cache.json', []);
    $hit = $all[$ip] ?? null;
    if (!$hit || $hit['expiresAt'] < time()) return null;
    return $hit['geo'];
}

function geo_cache_set($ip, $geo) {
    $all = db_read('geo-cache.json', []);
    // Prune expired entries so the file doesn't grow forever.
    $now = time();
    foreach ($all as $k => $v) { if ($v['expiresAt'] < $now) unset($all[$k]); }
    $all[$ip] = ['geo' => $geo, 'expiresAt' => $now + GEO_CACHE_TTL];
    db_write('geo-cache.json', $all);
}

function geo_lookup($ip) {
    $cached = geo_cache_get($ip);
    if ($cached !== null) return $cached;

    $url = 'http://ip-api.com/json/' . urlencode($ip) . '?fields=status,message,country,countryCode,regionName,city,zip,query';
    $ctx = stream_context_create(['http' => ['timeout' => 3]]);
    $body = @file_get_contents($url, false, $ctx);
    if ($body === false) return null;
    $j = json_decode($body, true);
    if (!$j || ($j['status'] ?? '') !== 'success') return null;
    geo_cache_set($ip, $j);
    return $j;
}

function append_click($record) {
    $clicks = db_read('clicks-log.json', []);
    $clicks[] = $record;
    db_write('clicks-log.json', $clicks);
}

function esc($s) { return htmlspecialchars((string) $s, ENT_QUOTES); }

function render_redirecting($destUrl) {
    $dest = esc($destUrl);
    echo <<<HTML
<!doctype html>
<html><head><meta charset="utf-8">
<title></title>
<meta http-equiv="refresh" content="0;url={$dest}">
</head>
<body></body></html>
HTML;
}

function render_rejected() {
    echo <<<HTML
<!doctype html>
<html><head><meta charset="utf-8">
<title></title>
</head>
<body></body></html>
HTML;
}

$offerId = $_GET['offer'] ?? '';

if ($offerId === '') {
    header('Content-Type: text/html');
    echo <<<HTML
<!doctype html>
<html><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Server is Running!</title>
<style>
  body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;
    background:#fff;color:#0f172a;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;}
  h1{margin:0;padding:16px;font-size:32px;font-weight:700;text-align:center;}
</style></head>
<body><h1>Server is Running!</h1></body></html>
HTML;
    exit;
}

$map = db_read('offers-map.json', []);
$dest = $map[$offerId] ?? null;
$ip = client_ip();
// Our click id for this visit (traffic link ?s2=...); postbacks are matched back to it.
$s2 = (string) ($_GET['s2'] ?? '');

if (!$dest) {
    append_click(['ts' => gmdate('c'), 'offerId' => $offerId, 'ip' => $ip, 'country' => null, 'region' => null, 'city' => null, 'zip' => null, 'valid' => false, 'redirected' => false, 'reason' => 'offer-not-found', 's2' => $s2]);
    http_response_code(404);
    header('Content-Type: text/plain');
    echo 'Offer not found: ' . $offerId;
    exit;
}

$geo = geo_lookup($ip);
$isUs = $geo && ($geo['countryCode'] ?? '') === 'US';

if (!$isUs) {
    append_click([
        'ts' => gmdate('c'), 'offerId' => $offerId, 'ip' => $ip,
        'country' => $geo['country'] ?? null, 'region' => $geo['regionName'] ?? null, 'city' => $geo['city'] ?? null, 'zip' => $geo['zip'] ?? null,
        'valid' => false, 'redirected' => false, 'reason' => $geo ? 'non-us-ip' : 'geo-lookup-failed', 's2' => $s2,
    ]);
    header('Content-Type: text/html');
    render_rejected();
    exit;
}

// Merge incoming query params into the destination URL (incoming wins on key clash).
$parts = parse_url($dest);
parse_str($parts['query'] ?? '', $destParams);
$incoming = $_GET;
unset($incoming['offer']);
$merged = array_merge($destParams, $incoming);
$query = http_build_query($merged);
$scheme = $parts['scheme'] ?? 'https';
$host = $parts['host'] ?? '';
$path = $parts['path'] ?? '';
$finalUrl = $scheme . '://' . $host . $path . ($query ? '?' . $query : '');

append_click(['ts' => gmdate('c'), 'offerId' => $offerId, 'ip' => $ip, 'country' => $geo['country'], 'region' => $geo['regionName'], 'city' => $geo['city'], 'zip' => $geo['zip'] ?? null, 'valid' => true, 'redirected' => true, 'reason' => null, 's2' => $s2]);

header('Content-Type: text/html');
render_redirecting($finalUrl);
