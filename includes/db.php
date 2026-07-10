<?php
define('DATA_DIR', __DIR__ . '/../data');

function db_read($file, $default = []) {
    $path = DATA_DIR . '/' . $file;
    if (!file_exists($path)) return $default;
    $json = file_get_contents($path);
    $data = json_decode($json, true);
    return $data === null ? $default : $data;
}

function db_write($file, $data) {
    $path = DATA_DIR . '/' . $file;
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

/** Rebuilds offers-map.json (id -> link) from offers.json — called after every offer save/delete. */
function sync_offers_map() {
    $offers = db_read('offers.json', []);
    $map = [];
    foreach ($offers as $o) {
        if (empty($o['deleted']) && !empty($o['id']) && !empty($o['link'])) {
            $map[$o['id']] = $o['link'];
        }
    }
    db_write('offers-map.json', $map);
}
