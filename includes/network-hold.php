<?php
/**
 * Offers of a network that is paused (Not active) or "Active but pending" lose
 * their Priority star automatically. Called after network changes and whenever
 * the Offers page loads, so the stars always match the networks' state.
 */
require_once __DIR__ . '/db.php';

function networks_list() {
    $file = file_exists(DATA_DIR . '/networks-live.json') ? 'networks-live.json' : 'networks.json';
    return array_values(array_filter(db_read($file, []), fn($n) => empty($n['deleted'])));
}

/** The network record an offer belongs to: same name + identity, else the only network with that name. */
function offer_network($offer, $networks) {
    $lc = fn($v) => strtolower(trim((string) $v));
    if ($lc($offer['network'] ?? '') === '') return null;
    $sameName = array_values(array_filter($networks, fn($n) => $lc($n['name'] ?? '') === $lc($offer['network'] ?? '')));
    foreach ($sameName as $n) {
        if ($lc($n['identity'] ?? '') === $lc($offer['account'] ?? '')) return $n;
    }
    return count($sameName) === 1 ? $sameName[0] : null;
}

/** Paused (Not active) or Active but pending. */
function network_on_hold($n) {
    return $n && (($n['enabled'] ?? true) === false || !empty($n['pendingSince']));
}

/** Unstar every starred offer whose network is on hold. Returns how many changed. */
function unstar_offers_of_held_networks() {
    $networks = networks_list();
    $offers = db_read('offers.json', []);
    $changed = 0;
    foreach ($offers as &$o) {
        if (!empty($o['priority']) && network_on_hold(offer_network($o, $networks))) {
            $o['priority'] = false;
            $changed++;
        }
    }
    unset($o);
    if ($changed) db_write('offers.json', $offers);
    return $changed;
}
