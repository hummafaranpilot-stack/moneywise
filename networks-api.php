<?php
/**
 * Read-only networks list, manually mirrored from the Muneeb Data tool's
 * Networks tab. Not auto-synced on purpose — when a new network is added
 * in Muneeb, it gets copied here by hand on request.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_login_api();
no_cache_headers();

header('Content-Type: application/json');

$networks = db_read('networks.json', []);
echo json_encode(array_values(array_filter($networks, fn($n) => empty($n['deleted']))));
