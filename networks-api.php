<?php
/**
 * Read-only networks list, manually mirrored from the Muneeb Data tool's
 * Networks tab. Not auto-synced on purpose — when a new network is added
 * in Muneeb, it gets copied here by hand on request.
 *
 * Flat JSON array, one object per network. A downstream sync client reads
 * name, identity, paymentTerms, minPayout and status from it; status is
 * combined with the enabled flag, e.g. "approved, enabled". The offers page
 * dropdown also uses the remaining fields (id, email, telegram, trackers).
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_login_api();
no_cache_headers();

header('Content-Type: application/json');

$networks = db_read('networks.json', []);
$networks = array_values(array_filter($networks, fn($n) => empty($n['deleted'])));

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
