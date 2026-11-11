<?php
/**
 * Public, read-only list of network colors (no login needed), so other tools can
 * color things the same way the Offers page does.
 *
 * GET /network-colors.php → [{"id","name","identity","color"}, ...]
 *
 * Colors are assigned on the Networks page (networks-api.php); a network that
 * has not been given one yet is left out until that page or API is opened.
 */
require_once __DIR__ . '/includes/db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-store, max-age=0');

$file = file_exists(DATA_DIR . '/networks-live.json') ? 'networks-live.json' : 'networks.json';
$out = [];
foreach (db_read($file, []) as $n) {
    if (!empty($n['deleted']) || empty($n['color'])) continue;
    $out[] = [
        'id'       => $n['id'] ?? '',
        'name'     => $n['name'] ?? '',
        'identity' => $n['identity'] ?? '',
        'color'    => $n['color'],
    ];
}
echo json_encode($out);
