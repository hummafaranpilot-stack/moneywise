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
<title>MoneyWise — Statistics</title>
<style>
  * { box-sizing: border-box; }
  body {
    margin: 0; min-height: 100vh; padding: 40px 24px;
    background: #0b1220; color: #e6ebf5;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  }
  .wrap { max-width: 960px; margin: 0 auto; }
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
  button.ghost { background: transparent; border: 1px solid #253046; color: #e6ebf5; font-weight: 600; }
  button.preset { background: transparent; border: 1px solid #253046; color: #94a3b8; font-weight: 600; padding: 9px 16px; }
  button.preset.active { background: #16a34a; border-color: #16a34a; color: #06210f; }
  .presets { display: flex; gap: 8px; margin-bottom: 14px; }
  table { width: 100%; border-collapse: collapse; background: #121a2b; border-radius: 12px; overflow: hidden; }
  th, td { padding: 12px 16px; text-align: left; font-size: 13.5px; }
  th { background: #0f1626; color: #94a3b8; font-weight: 600; font-size: 11.5px; text-transform: uppercase; letter-spacing: .04em; }
  tbody tr:not(:last-child) td { border-bottom: 1px solid #1c2536; }
  tbody tr:hover { background: #16203a; }
  td.num { font-variant-numeric: tabular-nums; font-weight: 600; }
  .cr { color: #4ade80; font-size: 11.5px; margin-left: 6px; }
  tfoot td { font-weight: 700; border-top: 2px solid #253046; background: #0f1626; }
  .empty { text-align: center; padding: 40px; color: #64748b; font-size: 13px; }
</style>
</head>
<body>
<div class="wrap">
  <div class="brand">MoneyWise — Statistics</div>
  <div class="sub">Click, validation, and redirect counts per offer.</div>

  <div class="presets" id="presets">
    <button class="preset" data-preset="today" onclick="applyPreset('today')">Today</button>
    <button class="preset" data-preset="yesterday" onclick="applyPreset('yesterday')">Yesterday</button>
    <button class="preset" data-preset="all" onclick="applyPreset('all')">All Time</button>
  </div>

  <div class="filters">
    <div class="field">
      <label for="from">From</label>
      <input id="from" type="date" onchange="clearPresetHighlight();loadStats()">
    </div>
    <div class="field">
      <label for="to">To</label>
      <input id="to" type="date" onchange="clearPresetHighlight();loadStats()">
    </div>
    <button onclick="clearPresetHighlight();loadStats()">Apply</button>
  </div>

  <div id="tableWrap">
    <div class="empty">Loading…</div>
  </div>

  <div class="sub" style="margin-top:32px;margin-bottom:8px;">Recent Clicks — per-click location detail (latest 200)</div>
  <div id="clicksTableWrap">
    <div class="empty">Loading…</div>
  </div>
</div>

<script>
function fmtPct(n, d) { return d > 0 ? ((n / d) * 100).toFixed(1) + '%' : '—'; }

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
  loadStats();
}

function clearPresetHighlight() {
  document.querySelectorAll('#presets .preset').forEach(b => b.classList.remove('active'));
}

async function loadStats() {
  const from = document.getElementById('from').value;
  const to   = document.getElementById('to').value;
  const qs = new URLSearchParams();
  if (from) qs.set('from', from);
  if (to)   qs.set('to', to);

  try {
    const r = await fetch('stats-api.php?' + qs.toString());
    const data = await r.json();
    render(data);
    renderClicks(data.clicks || []);
  } catch (e) {
    document.getElementById('tableWrap').innerHTML = '<div class="empty">Could not load stats.</div>';
    document.getElementById('clicksTableWrap').innerHTML = '<div class="empty">Could not load clicks.</div>';
  }
}

function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }

function renderClicks(clicks) {
  const wrap = document.getElementById('clicksTableWrap');
  const rows = clicks.map(c => {
    const statusBadge = c.redirected
      ? '<span style="color:#4ade80;">✓ Redirected</span>'
      : '<span style="color:#f87171;">✗ ' + esc(c.reason || 'blocked') + '</span>';
    const time = new Date(c.ts).toLocaleString();
    return `
      <tr>
        <td>${esc(time)}</td>
        <td>${esc(c.offerId)}</td>
        <td>${esc(c.ip)}</td>
        <td>${esc(c.city)}</td>
        <td>${esc(c.region)}</td>
        <td>${esc(c.zip)}</td>
        <td>${statusBadge}</td>
      </tr>
    `;
  }).join('');
  wrap.innerHTML = `
    <table>
      <thead>
        <tr><th>Time</th><th>Offer</th><th>IP</th><th>City</th><th>State</th><th>Zip</th><th>Status</th></tr>
      </thead>
      <tbody>${rows || '<tr><td colspan="7" style="text-align:center;color:#64748b;padding:24px;">No clicks recorded yet for this range.</td></tr>'}</tbody>
    </table>
  `;
}

function render(data) {
  const wrap = document.getElementById('tableWrap');
  const rows = (data.offers || []).map(o => `
    <tr>
      <td>${o.offerId}</td>
      <td class="num">${o.total}</td>
      <td class="num">${o.valid}<span class="cr">${fmtPct(o.valid, o.total)}</span></td>
      <td class="num">${o.redirected}<span class="cr">${fmtPct(o.redirected, o.total)}</span></td>
    </tr>
  `).join('');
  const totals = data.totals || { total: 0, valid: 0, redirected: 0 };
  wrap.innerHTML = `
    <table>
      <thead>
        <tr><th>Offer</th><th>Total Clicks</th><th>Valid (US)</th><th>Redirected</th></tr>
      </thead>
      <tbody>${rows || '<tr><td colspan="4" style="text-align:center;color:#64748b;padding:24px;">No clicks recorded yet for this range.</td></tr>'}</tbody>
      <tfoot>
        <tr><td>Total</td><td class="num">${totals.total}</td><td class="num">${totals.valid}</td><td class="num">${totals.redirected}</td></tr>
      </tfoot>
    </table>
  `;
}

applyPreset('today');
</script>
</body>
</html>
