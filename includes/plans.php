<?php
/**
 * Weekly plans (Plans page). A plan covers one Monday to Sunday week and holds:
 *   networks: { networkId: "active" | "pending" | "paused" }
 *   offers:   { offerId: true | false }   (Priority star)
 * When its week starts, the plan is applied once: networks and offer stars are set to
 * what it says. Anything changed by hand after that stays as changed.
 * Stored in data/plans.json (gitignored, live data).
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/network-hold.php';

// The admin's week runs Monday to Sunday in Pakistan time.
const PLAN_TZ = 'Asia/Karachi';
const PLAN_STATES = ['active', 'pending', 'paused'];

function plan_today() {
    return (new DateTime('now', new DateTimeZone(PLAN_TZ)))->format('Y-m-d');
}

/** The Monday of the week that contains $ymd (YYYY-MM-DD), or null for a bad date. */
function week_monday($ymd) {
    $d = DateTime::createFromFormat('!Y-m-d', (string) $ymd);
    if (!$d || $d->format('Y-m-d') !== $ymd) return null;
    $n = (int) $d->format('N');
    if ($n > 1) $d->modify('-' . ($n - 1) . ' days');
    return $d->format('Y-m-d');
}

function week_sunday($monday) {
    $d = DateTime::createFromFormat('!Y-m-d', $monday);
    $d->modify('+6 days');
    return $d->format('Y-m-d');
}

function plans_read() { return db_read('plans.json', []); }
function plans_write($plans) { db_write('plans.json', array_values($plans)); }

/** Set networks and offer stars to what the plan says, then mark it applied. */
function apply_plan(&$plan) {
    $netFile = 'networks-live.json';
    $networks = file_exists(DATA_DIR . '/' . $netFile) ? db_read($netFile, []) : db_read('networks.json', []);
    $want = $plan['networks'] ?? [];
    foreach ($networks as &$n) {
        $id = $n['id'] ?? '';
        if ($id === '' || !isset($want[$id])) continue;
        $state = $want[$id];
        if ($state === 'paused') {
            $n['enabled'] = false;
            $n['pendingSince'] = '';
        } elseif ($state === 'pending') {
            $n['enabled'] = true;
            // Keep the original date if it was already pending; otherwise it starts this week.
            if (empty($n['pendingSince'])) $n['pendingSince'] = $plan['weekStart'];
        } else {
            $n['enabled'] = true;
            $n['pendingSince'] = '';
        }
        $n['updatedAt'] = gmdate('c');
    }
    unset($n);
    db_write($netFile, $networks);

    $stars = $plan['offers'] ?? [];
    if ($stars) {
        $offers = db_read('offers.json', []);
        foreach ($offers as &$o) {
            $id = $o['id'] ?? '';
            if ($id !== '' && array_key_exists($id, $stars)) $o['priority'] = (bool) $stars[$id];
        }
        unset($o);
        db_write('offers.json', $offers);
    }
    // A starred offer of a network the plan pauses or holds still loses its star.
    unstar_offers_of_held_networks();

    $plan['appliedAt'] = gmdate('c');
}

/** Apply this week's plan if it hasn't been applied yet. Cheap when there is nothing to do. */
function apply_due_plans() {
    if (!file_exists(DATA_DIR . '/plans.json')) return false;
    $plans = plans_read();
    $monday = week_monday(plan_today());
    foreach ($plans as &$p) {
        if (($p['weekStart'] ?? '') === $monday && empty($p['appliedAt'])) {
            apply_plan($p);
            unset($p);
            plans_write($plans);
            return true;
        }
    }
    unset($p);
    return false;
}
