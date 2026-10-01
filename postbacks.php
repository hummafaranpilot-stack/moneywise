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
<title>MoneyWise — Postbacks</title>
<style>
  * { box-sizing: border-box; }
  body {
    margin: 0; min-height: 100vh; padding: 40px 24px;
    background: #0b1220; color: #e6ebf5;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  }
  .wrap { max-width: 1100px; margin: 0 auto; }
  .brand { font-size: 24px; font-weight: 800; color: #22c55e; margin-bottom: 4px; }
  .sub { color: #94a3b8; font-size: 13px; margin-bottom: 28px; }
  .filters {
    display: flex; align-items: end; gap: 14px; margin-bottom: 24px;
    background: #121a2b; padding: 18px 20px; border-radius: 12px;
  }
  .field { display: flex; flex-direction: column; gap: 6px; }
  .field label { font-size: 11.5px; color: #94a3b8; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; }
  .field input {
    background: #0b1220; border: 1px solid #253046; color: #e6ebf5;
    padding: 9px 12px; border-radius: 8px; font-size: 13px;
  }
  button {
    background: #22c55e; color: #06210f; border: none; font-weight: 700;
    padding: 10px 18px; border-radius: 8px; cursor: pointer; font-size: 13px;
  }
  button.preset { background: transparent; border: 1px solid #253046; color: #94a3b8; font-weight: 600; padding: 9px 16px; }
  button.preset.active { background: #16a34a; border-color: #16a34a; color: #06210f; }
  .presets { display: flex; gap: 8px; margin-bottom: 14px; }
  .tiles { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-bottom: 24px; }
  .tile { background: #121a2b; border-radius: 12px; padding: 16px 18px; }
  .tile .label { font-size: 11.5px; color: #94a3b8; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; }
  .tile .value { font-size: 24px; font-weight: 800; margin-top: 6px; font-variant-numeric: tabular-nums; }
  .table-scroll { overflow-x: auto; border-radius: 12px; }
  table { width: 100%; border-collapse: collapse; background: #121a2b; border-radius: 12px; overflow: hidden; }
  th, td { padding: 12px 16px; text-align: left; font-size: 13.5px; white-space: nowrap; }
  th { background: #0f1626; color: #94a3b8; font-weight: 600; font-size: 11.5px; text-transform: uppercase; letter-spacing: .04em; }
  tbody tr:not(:last-child) td { border-bottom: 1px solid #1c2536; }
  tbody tr:hover { background: #16203a; }
  td.num { font-variant-numeric: tabular-nums; font-weight: 600; }
  td.mono { font-family: ui-monospace, Menlo, monospace; font-size: 12px; color: #94a3b8; }
  tfoot td { font-weight: 700; border-top: 2px solid #253046; background: #0f1626; }
  .empty { text-align: center; padding: 40px; color: #64748b; font-size: 13px; }
  .badge { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 11.5px; font-weight: 700; }
  .s-approved { color: #4ade80; background: #0f2418; }
  .s-pending  { color: #fbbf24; background: #2a2110; }
  .s-hold     { color: #60a5fa; background: #102036; }
  .s-declined { color: #f87171; background: #2a1414; }
  .s-unknown  { color: #94a3b8; background: #1c2536; }
  .c-approved { color: #4ade80; } .c-pending { color: #fbbf24; } .c-hold { color: #60a5fa; } .c-declined { color: #f87171; }
</style>
</head>
<body>
<div class="wrap">
  <div class="brand">MoneyWise — Postbacks</div>
  <div class="sub">Conversions reported by networks via postback.php. Each conversion is counted once, with its latest status.</div>

  <div class="presets" id="presets">
    <button class="preset" data-preset="today" onclick="applyPreset('today')">Today</button>
    <button class="preset" data-preset="yesterday" onclick="applyPreset('yesterday')">Yesterday</button>
    <button class="preset" data-preset="all" onclick="applyPreset('all')">All Time</button>
  </div>

  <div class="filters">
    <div class="field">
      <label for="from">From</label>
      <input id="from" type="date" onchange="clearPresetHighlight();loadPostbacks()">
    </div>
    <div class="field">
      <label for="to">To</label>
      <input id="to" type="date" onchange="clearPresetHighlight();loadPostbacks()">
    </div>
    <button onclick="clearPresetHighlight();loadPostbacks()">Apply</button>
  </div>

  <div class="tiles" id="tiles"></div>

  <div id="tableWrap">
    <div class="empty">Loading…</div>
  </div>

  <div class="sub" style="margin-top:32px;margin-bottom:8px;">Recent Conversions (latest 200)</div>
  <div id="convTableWrap">
    <div class="empty">Loading…</div>
  </div>
</div>

<script>
function _ymd(d) {
  return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
}

function applyPreset(name) {
  const today = new Date();
  if (name === 'today') {
    document.getElementById('from').value = _ymd(today);
    document.getElementById('to').value   = _ymd(today);
  } else if (name === 'yesterday') {
    const y = new Date(today); y.setDate(y.getDate() - 1);
    document.getElementById('from').value = _ymd(y);
    document.getElementById('to').value   = _ymd(y);
  } else {
    document.getElementById('from').value = '';
    document.getElementById('to').value   = '';
  }
  document.querySelectorAll('#presets .preset').forEach(b => b.classList.toggle('active', b.dataset.preset === name));
  loadPostbacks();
}

function clearPresetHighlight() {
  document.querySelectorAll('#presets .preset').forEach(b => b.classList.remove('active'));
}

function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
function money(n) { return '$' + Number(n || 0).toFixed(2); }

async function loadPostbacks() {
  const from = document.getElementById('from').value;
  const to   = document.getElementById('to').value;
  const qs = new URLSearchParams();
  if (from) qs.set('from', from);
  if (to)   qs.set('to', to);

  try {
    const r = await fetch('postbacks-api.php?' + qs.toString());
    const data = await r.json();
    renderTiles(data.totals || {});
    renderOffers(data);
    renderConversions(data.conversions || []);
  } catch (e) {
    document.getElementById('tiles').innerHTML = '';
    document.getElementById('tableWrap').innerHTML = '<div class="empty">Could not load postbacks.</div>';
    document.getElementById('convTableWrap').innerHTML = '';
  }
}

function renderTiles(t) {
  const tiles = [
    ['Conversions', t.total || 0, ''],
    ['Approved', t.approved || 0, 'c-approved'],
    ['Pending', t.pending || 0, 'c-pending'],
    ['Hold', t.hold || 0, 'c-hold'],
    ['Declined', t.declined || 0, 'c-declined'],
    ['Approved Payout', money(t.approvedPayout), 'c-approved'],
    ['Pending + Hold Payout', money(t.pendingPayout), 'c-pending'],
  ];
  document.getElementById('tiles').innerHTML = tiles.map(([label, value, cls]) =>
    `<div class="tile"><div class="label">${label}</div><div class="value ${cls}">${value}</div></div>`
  ).join('');
}

function renderOffers(data) {
  const rows = (data.offers || []).map(o => `
    <tr>
      <td>${esc(o.offerName)}</td>
      <td class="num">${o.total}</td>
      <td class="num c-approved">${o.approved}</td>
      <td class="num c-pending">${o.pending}</td>
      <td class="num c-hold">${o.hold}</td>
      <td class="num c-declined">${o.declined}</td>
      <td class="num">${money(o.approvedPayout)}</td>
      <td class="num">${money(o.pendingPayout)}</td>
    </tr>
  `).join('');
  const t = data.totals || {};
  document.getElementById('tableWrap').innerHTML = `
    <div class="table-scroll"><table>
      <thead>
        <tr><th>Offer</th><th>Conversions</th><th>Approved</th><th>Pending</th><th>Hold</th><th>Declined</th><th>Approved $</th><th>Pending + Hold $</th></tr>
      </thead>
      <tbody>${rows || '<tr><td colspan="8" style="text-align:center;color:#64748b;padding:24px;">No postbacks received yet for this range.</td></tr>'}</tbody>
      <tfoot>
        <tr><td>Total</td><td class="num">${t.total || 0}</td><td class="num">${t.approved || 0}</td><td class="num">${t.pending || 0}</td><td class="num">${t.hold || 0}</td><td class="num">${t.declined || 0}</td><td class="num">${money(t.approvedPayout)}</td><td class="num">${money(t.pendingPayout)}</td></tr>
      </tfoot>
    </table></div>
  `;
}

function renderConversions(convs) {
  const rows = convs.map(c => `
    <tr>
      <td>${esc(new Date(c.ts).toLocaleString())}</td>
      <td>${esc(c.offerName)}</td>
      <td><span class="badge s-${esc(c.status)}">${esc(c.status)}</span>${c.updates > 1 ? ' <span style="color:#64748b;font-size:11.5px;">×' + c.updates + '</span>' : ''}</td>
      <td class="num">${esc(Number(c.payout || 0).toFixed(2))} ${esc(c.currency)}</td>
      <td>${esc(c.goal)}</td>
      <td>${esc(c.geo)}</td>
      <td class="mono">${esc(c.sub1)}</td>
      <td class="mono">${esc(c.clickid)}</td>
      <td class="mono">${esc(c.txid)}</td>
    </tr>
  `).join('');
  document.getElementById('convTableWrap').innerHTML = `
    <div class="table-scroll"><table>
      <thead>
        <tr><th>Last Update</th><th>Offer</th><th>Status</th><th>Payout</th><th>Goal</th><th>Geo</th><th>Sub1</th><th>Click ID</th><th>Transaction ID</th></tr>
      </thead>
      <tbody>${rows || '<tr><td colspan="9" style="text-align:center;color:#64748b;padding:24px;">No postbacks received yet for this range.</td></tr>'}</tbody>
    </table></div>
  `;
}

applyPreset('today');
</script>
</body>
</html>
