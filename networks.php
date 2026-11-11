<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_login();
no_cache_headers();
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>MoneyWise — Networks</title>
<style>
  * { box-sizing: border-box; }
  html, body { overflow-x: hidden; }
  body {
    margin: 0; min-height: 100vh; padding: 32px 24px;
    background: #f8fafc; color: #0f172a;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  }
  .topbar { max-width: 1400px; margin: 0 auto 20px; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
  .brand { font-size: 22px; font-weight: 800; color: #16a34a; }
  .topbar-right { display: flex; align-items: center; gap: 10px; }
  .nav-link { color: #475569; font-size: 13.5px; font-weight: 600; text-decoration: none; padding: 9px 14px; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; }
  .nav-link:hover { color: #16a34a; border-color: #bbf7d0; }
  .btn-add {
    background: #16a34a; color: #fff; border: none; font-weight: 700;
    padding: 10px 18px; border-radius: 8px; cursor: pointer; font-size: 13.5px;
  }
  .toolbar { max-width: 1400px; margin: 0 auto 14px; display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
  .toolbar input { flex: 1; min-width: 220px; max-width: 360px; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; background: #fff; }
  .count { font-size: 12.5px; color: #64748b; }

  .table-wrap { max-width: 1400px; margin: 0 auto; background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; overflow-x: auto; }
  table { width: 100%; border-collapse: collapse; }
  th, td { padding: 11px 14px; text-align: left; font-size: 13px; white-space: nowrap; }
  th { background: #f8fafc; color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; border-bottom: 1px solid #e2e8f0; }
  tbody tr:not(:last-child) td { border-bottom: 1px solid #f1f5f9; }
  tbody tr:hover { background: #f8fafc; }
  tr.net-disabled td:not(.keep) { opacity: 0.5; }
  td.notes { white-space: normal; min-width: 180px; max-width: 280px; color: #475569; font-size: 12.5px; line-height: 1.45; }
  .net-name { font-weight: 700; }
  .muted { color: #94a3b8; }
  .pill { display: inline-block; padding: 2px 9px; border-radius: 999px; font-size: 11px; font-weight: 700; }
  .st-approved { background: #f0fdf4; color: #166534; }
  .st-pending  { background: #fffbeb; color: #92400e; }
  .st-rejected { background: #fef2f2; color: #b91c1c; }
  .st-other    { background: #f1f5f9; color: #475569; }
  .payout { font-weight: 700; font-variant-numeric: tabular-nums; }

  /* 3-way switch: left = not active, middle = active but pending, right = active */
  .toggle { position: relative; width: 48px; height: 20px; border: none; border-radius: 999px; cursor: pointer; padding: 0; background: #cbd5e1; vertical-align: middle; }
  .toggle.on { background: #16a34a; }
  .toggle.pending { background: #f59e0b; }
  .toggle-knob { position: absolute; top: 2px; left: 2px; width: 16px; height: 16px; background: #fff; border-radius: 50%; transition: transform 0.16s; }
  .toggle.pending .toggle-knob { transform: translateX(14px); }
  .toggle.on .toggle-knob { transform: translateX(28px); }
  .state-label { display: block; font-size: 10.5px; font-weight: 700; margin-top: 4px; white-space: nowrap; }
  .state-label.on { color: #16a34a; }
  .state-label.pending { color: #b45309; }
  .state-label.off { color: #94a3b8; }

  .actions { display: flex; gap: 4px; }
  .actions button { background: #fff; border: 1px solid #e2e8f0; cursor: pointer; padding: 6px; border-radius: 7px; color: #64748b; display: inline-flex; }
  .actions button:hover { background: #eff6ff; color: #16a34a; border-color: #93c5fd; }
  .actions button.del:hover { background: #fee2e2; color: #b91c1c; border-color: #fca5a5; }
  .actions svg { width: 13px; height: 13px; }
  .empty { text-align: center; padding: 50px; color: #94a3b8; }

  /* Modal — same as the Offers page */
  .modal-back { position: fixed; inset: 0; background: rgba(15,23,42,.45); display: none; align-items: center; justify-content: center; z-index: 100; padding: 24px; }
  .modal-back.open { display: flex; }
  .modal { background: #fff; border-radius: 16px; padding: 28px; width: 640px; max-width: 100%; max-height: 92vh; overflow-y: auto; }
  .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
  .modal-header h2 { margin: 0; font-size: 20px; }
  .btn-close { background: none; border: none; cursor: pointer; color: #64748b; }
  .field { margin-bottom: 14px; }
  .field-label { display: block; font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 6px; }
  .field-label .req { color: #ef4444; }
  .field-label .hint { font-weight: 400; color: #94a3b8; font-size: 11px; }
  .field input, .field select, .field textarea { width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; background: #fff; font-family: inherit; }
  .field textarea { resize: vertical; min-height: 60px; }
  .row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
  @media (max-width: 560px) { .row-2 { grid-template-columns: 1fr; } }
  .check { display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; color: #475569; }
  .modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 8px; }
  .btn-secondary { background: #f1f5f9; border: 1px solid #cbd5e1; padding: 9px 16px; border-radius: 8px; cursor: pointer; font-weight: 600; }
  .btn-primary { background: #16a34a; color: #fff; border: none; padding: 9px 18px; border-radius: 8px; cursor: pointer; font-weight: 700; }
</style>
</head>
<body>

<div class="topbar">
  <div class="brand">MoneyWise — Networks</div>
  <div class="topbar-right">
    <a class="nav-link" href="offers.php">Offers</a>
    <button class="btn-add" onclick="openNetworkModal()">+ Add Network</button>
  </div>
</div>

<div class="toolbar">
  <input id="search" type="search" placeholder="Search name, identity, FB page, notes…" oninput="render()">
  <span class="count" id="count"></span>
</div>

<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>State</th><th>Name</th><th>Identity</th><th>Payment Terms</th><th>Min Payout</th>
        <th>Owner Tracker</th><th>Status</th><th>Contact</th><th>FB Page</th><th>Tracker Domain</th><th>Notes</th><th></th>
      </tr>
    </thead>
    <tbody id="rows"><tr><td colspan="12" class="empty">Loading…</td></tr></tbody>
  </table>
</div>

<!-- ADD/EDIT MODAL -->
<div class="modal-back" id="network-modal" onclick="if(event.target===this) closeNetworkModal()">
  <div class="modal">
    <div class="modal-header">
      <h2 id="network-modal-title">Add Network</h2>
      <button class="btn-close" onclick="closeNetworkModal()">✕</button>
    </div>
    <div class="row-2">
      <div class="field">
        <label class="field-label" for="nw-name">Network Name<span class="req">*</span></label>
        <input id="nw-name" type="text" placeholder="e.g. ClickStack">
      </div>
      <div class="field">
        <label class="field-label" for="nw-identity">Identity</label>
        <input id="nw-identity" type="text" placeholder="e.g. Rajpurohit">
      </div>
    </div>
    <div class="row-2">
      <div class="field">
        <label class="field-label" for="nw-terms">Payment Terms</label>
        <input id="nw-terms" type="text" placeholder="e.g. Weekly Net-0">
      </div>
      <div class="field">
        <label class="field-label" for="nw-payout">Min Payout / Threshold</label>
        <input id="nw-payout" type="text" placeholder="e.g. $1000">
      </div>
    </div>
    <div class="row-2">
      <div class="field">
        <label class="field-label" for="nw-email">Account Email</label>
        <input id="nw-email" type="text">
      </div>
      <div class="field">
        <label class="field-label" for="nw-telegram">Telegram</label>
        <input id="nw-telegram" type="text" placeholder="@username">
      </div>
    </div>
    <div class="row-2">
      <div class="field">
        <label class="field-label" for="nw-fb-page">FB Page <span class="muted">(optional)</span></label>
        <input id="nw-fb-page" type="text" placeholder="Page used to run ads on this network">
      </div>
      <div class="field">
        <label class="field-label" for="nw-tracker-domain">Tracker Domain <span class="muted">(optional)</span></label>
        <input id="nw-tracker-domain" type="text" placeholder="e.g. risingmax.net">
      </div>
    </div>
    <div class="row-2">
      <div class="field">
        <label class="field-label" for="nw-owner-tracker">Owner Tracker</label>
        <input id="nw-owner-tracker" type="text" placeholder="e.g. Everflow">
      </div>
      <div class="field">
        <label class="field-label" for="nw-our-tracker">Our Tracker</label>
        <input id="nw-our-tracker" type="text">
      </div>
    </div>
    <div class="row-2">
      <div class="field">
        <label class="field-label" for="nw-methods">Payment Methods</label>
        <input id="nw-methods" type="text">
      </div>
      <div class="field">
        <label class="field-label" for="nw-manager">Manager</label>
        <input id="nw-manager" type="text">
      </div>
    </div>
    <div class="row-2">
      <div class="field">
        <label class="field-label" for="nw-status">Status</label>
        <select id="nw-status">
          <option value="approved">approved</option>
          <option value="pending">pending</option>
          <option value="rejected">rejected</option>
        </select>
      </div>
      <div class="field">
        <label class="field-label" for="nw-state">State</label>
        <select id="nw-state" onchange="onStateChange()">
          <option value="active">Active</option>
          <option value="pending">Active but pending</option>
          <option value="inactive">Not active</option>
        </select>
      </div>
    </div>
    <div class="field" id="nw-pending-wrap" style="display:none;">
      <label class="field-label" for="nw-pending-since">Pending since</label>
      <input id="nw-pending-since" type="date">
    </div>
    <div class="field">
      <label class="field-label" for="nw-notes">Notes</label>
      <textarea id="nw-notes"></textarea>
    </div>
    <div class="modal-actions">
      <button class="btn-secondary" onclick="closeNetworkModal()">Cancel</button>
      <button class="btn-primary" onclick="submitNetworkForm()">Save Network</button>
    </div>
  </div>
</div>

<script>
let _networks = [];
let _editingId = null;

function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
function $(id) { return document.getElementById(id); }
// A network without an "enabled" flag was never disabled.
function isEnabled(n) { return n.enabled !== false; }
// 'active' | 'pending' (active but pending, since n.pendingSince) | 'inactive'
function netState(n) { return !isEnabled(n) ? 'inactive' : (n.pendingSince ? 'pending' : 'active'); }
const STATE_RANK = { active: 2, pending: 1, inactive: 0 };
function todayYmd() {
  const d = new Date();
  return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
}
function fmtDate(ymd) {
  const [y, m, d] = ymd.split('-').map(Number);
  return new Date(y, m - 1, d).toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' });
}
function daysSince(ymd) {
  const [y, m, d] = ymd.split('-').map(Number);
  const now = new Date();
  return Math.round((new Date(now.getFullYear(), now.getMonth(), now.getDate()) - new Date(y, m - 1, d)) / 86400000);
}

async function api(body) {
  const r = await fetch('networks-api.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) });
  if (!r.ok) { const e = await r.json().catch(() => ({})); throw new Error(e.error || ('HTTP ' + r.status)); }
  return r.json();
}

async function loadNetworks() {
  const r = await fetch('networks-api.php?raw=1');
  _networks = await r.json();
  render();
}

const EDIT_ICON = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>';
const DEL_ICON = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>';

function render() {
  const q = $('search').value.trim().toLowerCase();
  const list = _networks
    .filter(n => !q || [n.name, n.identity, n.ownerTracker, n.ourTracker, n.paymentTerms, n.email, n.telegram, n.fbPage, n.trackerDomain, n.notes]
      .some(v => (v || '').toLowerCase().includes(q)))
    // Enabled first, then A→Z by name and identity.
    .sort((a, b) => (STATE_RANK[netState(b)] - STATE_RANK[netState(a)])
      || (a.name || '').localeCompare(b.name || '', undefined, { sensitivity: 'base' })
      || (a.identity || '').localeCompare(b.identity || '', undefined, { sensitivity: 'base' }));

  const activeCount = _networks.filter(n => netState(n) === 'active').length;
  const pendingCount = _networks.filter(n => netState(n) === 'pending').length;
  $('count').textContent = _networks.length + ' networks · ' + activeCount + ' active · ' + pendingCount + ' pending';

  if (!list.length) {
    $('rows').innerHTML = '<tr><td colspan="12" class="empty">' + (q ? 'No networks match.' : 'No networks yet — click "+ Add Network".') + '</td></tr>';
    return;
  }

  $('rows').innerHTML = list.map(n => {
    const on = isEnabled(n);
    const state = netState(n);
    const toggleClass = state === 'active' ? 'on' : state === 'pending' ? 'pending' : '';
    const NEXT_LABEL = { active: 'Active — click for Active but pending', pending: 'Active but pending — click for Not active', inactive: 'Not active — click for Active' };
    let stateLabel = '<span class="state-label on">Active</span>';
    if (state === 'inactive') stateLabel = '<span class="state-label off">Not active</span>';
    if (state === 'pending') {
      const days = daysSince(n.pendingSince);
      stateLabel = '<span class="state-label pending">Pending since ' + esc(fmtDate(n.pendingSince)) + '</span>'
        + '<span class="state-label pending" style="font-weight:600;margin-top:1px;">' + days + (days === 1 ? ' day' : ' days') + ' ago</span>';
    }
    const st = (n.status || '').toLowerCase();
    const stClass = ['approved', 'pending', 'rejected'].includes(st) ? 'st-' + st : 'st-other';
    const tg = (n.telegram || '').replace(/^@/, '');
    const contact = [n.email ? esc(n.email) : '', tg ? '@' + esc(tg) : ''].filter(Boolean).join(' · ');
    return `
      <tr class="${on ? '' : 'net-disabled'}">
        <td class="keep"><button class="toggle ${toggleClass}" onclick="cycleState('${esc(n.id)}')" title="${NEXT_LABEL[state]}"><span class="toggle-knob"></span></button>${stateLabel}</td>
        <td class="net-name">${esc(n.name)}</td>
        <td>${n.identity ? esc(n.identity) : '<span class="muted">—</span>'}</td>
        <td>${n.paymentTerms ? esc(n.paymentTerms) : '<span class="muted">—</span>'}</td>
        <td class="payout">${n.minPayout ? esc(n.minPayout) : '<span class="muted">—</span>'}</td>
        <td>${n.ownerTracker ? esc(n.ownerTracker) : '<span class="muted">—</span>'}</td>
        <td><span class="pill ${stClass}">${esc(n.status || '—')}</span></td>
        <td>${contact || '<span class="muted">—</span>'}</td>
        <td>${n.fbPage ? esc(n.fbPage) : '<span class="muted">—</span>'}</td>
        <td>${n.trackerDomain ? esc(n.trackerDomain) : '<span class="muted">—</span>'}</td>
        <td class="notes">${n.notes ? esc(n.notes).replace(/\n/g, '<br>') : '<span class="muted">—</span>'}</td>
        <td class="keep"><div class="actions">
          <button onclick="editNetwork('${esc(n.id)}')" title="Edit">${EDIT_ICON}</button>
          <button class="del" onclick="deleteNetwork('${esc(n.id)}')" title="Delete">${DEL_ICON}</button>
        </div></td>
      </tr>`;
  }).join('');
}

function openNetworkModal(n) {
  _editingId = n ? n.id : null;
  $('network-modal-title').textContent = n ? 'Edit Network' : 'Add Network';
  $('nw-name').value = n ? (n.name || '') : '';
  $('nw-identity').value = n ? (n.identity || '') : '';
  $('nw-terms').value = n ? (n.paymentTerms || '') : '';
  $('nw-payout').value = n ? (n.minPayout || '') : '';
  $('nw-email').value = n ? (n.email || '') : '';
  $('nw-telegram').value = n ? (n.telegram || '') : '';
  $('nw-fb-page').value = n ? (n.fbPage || '') : '';
  $('nw-tracker-domain').value = n ? (n.trackerDomain || '') : '';
  $('nw-owner-tracker').value = n ? (n.ownerTracker || '') : '';
  $('nw-our-tracker').value = n ? (n.ourTracker || '') : '';
  $('nw-methods').value = n ? (n.paymentMethods || '') : '';
  $('nw-manager').value = n ? (n.manager || '') : '';
  $('nw-status').value = n && ['pending', 'rejected'].includes(n.status) ? n.status : 'approved';
  $('nw-state').value = n ? netState(n) : 'active';
  $('nw-pending-since').value = (n && n.pendingSince) || todayYmd();
  onStateChange();
  $('nw-notes').value = n ? (n.notes || '') : '';
  $('network-modal').classList.add('open');
  $('nw-name').focus();
}

function onStateChange() {
  $('nw-pending-wrap').style.display = $('nw-state').value === 'pending' ? '' : 'none';
}

function closeNetworkModal() { $('network-modal').classList.remove('open'); _editingId = null; }

async function submitNetworkForm() {
  const name = $('nw-name').value.trim();
  if (!name) { alert('Network name required'); return; }
  const payload = {
    _action: _editingId ? 'update' : 'create',
    id: _editingId,
    name,
    identity: $('nw-identity').value,
    paymentTerms: $('nw-terms').value,
    minPayout: $('nw-payout').value,
    email: $('nw-email').value,
    telegram: $('nw-telegram').value,
    fbPage: $('nw-fb-page').value,
    trackerDomain: $('nw-tracker-domain').value,
    ownerTracker: $('nw-owner-tracker').value,
    ourTracker: $('nw-our-tracker').value,
    paymentMethods: $('nw-methods').value,
    manager: $('nw-manager').value,
    status: $('nw-status').value,
    enabled: $('nw-state').value !== 'inactive',
    pendingSince: $('nw-state').value === 'pending' ? ($('nw-pending-since').value || todayYmd()) : '',
    notes: $('nw-notes').value,
  };
  try {
    await api(payload);
    closeNetworkModal();
    await loadNetworks();
  } catch (e) { alert('Save failed: ' + e.message); }
}

function editNetwork(id) {
  const n = _networks.find(x => x.id === id);
  if (n) openNetworkModal(n);
}

async function deleteNetwork(id) {
  const n = _networks.find(x => x.id === id);
  if (!n || !confirm('Delete network "' + n.name + (n.identity ? ' · ' + n.identity : '') + '"?')) return;
  try {
    await api({ _action: 'delete', id });
    await loadNetworks();
  } catch (e) { alert('Delete failed: ' + e.message); }
}

// Click cycles: Active → Active but pending (dated today) → Not active → Active.
async function cycleState(id) {
  const n = _networks.find(x => x.id === id);
  if (!n) return;
  const prev = { enabled: n.enabled, pendingSince: n.pendingSince };
  const state = netState(n);
  const next = state === 'active' ? { enabled: true, pendingSince: todayYmd() }
             : state === 'pending' ? { enabled: false, pendingSince: '' }
             : { enabled: true, pendingSince: '' };
  Object.assign(n, next); render(); // optimistic
  try {
    await api({ _action: 'update', id, ...next });
  } catch (e) { Object.assign(n, prev); render(); alert('Failed: ' + e.message); }
}

loadNetworks();
</script>
</body>
</html>
