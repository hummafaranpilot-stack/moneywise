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
<title>MoneyWise — Plans</title>
<style>
  * { box-sizing: border-box; }
  html, body { overflow-x: hidden; }
  body { margin: 0; min-height: 100vh; padding: 32px 24px; background: #f8fafc; color: #0f172a;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
  .topbar { max-width: 1400px; margin: 0 auto 20px; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
  .brand { font-size: 22px; font-weight: 800; color: #16a34a; }
  .topbar-right { display: flex; align-items: center; gap: 10px; }
  .nav-link { color: #475569; font-size: 13.5px; font-weight: 600; text-decoration: none; padding: 9px 14px; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; }
  .nav-link:hover { color: #16a34a; border-color: #bbf7d0; }
  .btn { border: none; font-weight: 700; padding: 10px 16px; border-radius: 8px; cursor: pointer; font-size: 13px; }
  .btn-green { background: #16a34a; color: #fff; }
  .btn-ghost { background: #fff; color: #334155; border: 1px solid #e2e8f0; }
  .btn-danger { background: #fff; color: #b91c1c; border: 1px solid #fecaca; }
  .btn:disabled { opacity: .5; cursor: default; }

  .layout { max-width: 1400px; margin: 0 auto; display: grid; grid-template-columns: 280px 1fr; gap: 18px; align-items: start; }
  @media (max-width: 860px) { .layout { grid-template-columns: 1fr; } }

  .plan-list { display: flex; flex-direction: column; gap: 8px; }
  .plan-item { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px 14px; cursor: pointer; }
  .plan-item:hover { border-color: #bbf7d0; }
  .plan-item.sel { border-color: #16a34a; box-shadow: 0 0 0 3px rgba(22,163,74,.12); }
  .plan-item .pi-name { font-weight: 700; font-size: 14px; display: flex; justify-content: space-between; gap: 8px; }
  .plan-item .pi-week { font-size: 12.5px; color: #64748b; margin-top: 3px; }
  .badge { font-size: 10.5px; font-weight: 700; padding: 2px 8px; border-radius: 999px; white-space: nowrap; }
  .b-now { background: #dcfce7; color: #166534; }
  .b-next { background: #e0f2fe; color: #075985; }
  .b-past { background: #f1f5f9; color: #64748b; }
  .empty { color: #94a3b8; font-size: 13px; padding: 24px; text-align: center; background: #fff; border: 1px dashed #e2e8f0; border-radius: 12px; }

  .editor { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 18px; min-width: 0; }
  .ed-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 6px; }
  .ed-name { font-size: 20px; font-weight: 800; border: 1px solid transparent; border-radius: 8px; padding: 4px 8px; margin-left: -8px; min-width: 0; width: 260px; }
  .ed-name:hover:not(:disabled), .ed-name:focus { border-color: #e2e8f0; outline: none; }
  .ed-name:disabled { background: transparent; color: #0f172a; }
  .ed-week { color: #64748b; font-size: 13px; margin-bottom: 4px; }
  .ed-note { font-size: 12.5px; color: #64748b; margin-bottom: 14px; }
  .ed-actions { display: flex; gap: 8px; flex-wrap: wrap; }
  .ed-tools { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; margin: 10px 0 12px; }
  .ed-tools input[type=search] { flex: 1; min-width: 200px; max-width: 320px; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; }
  .ed-tools label { font-size: 12.5px; color: #475569; display: flex; align-items: center; gap: 6px; cursor: pointer; }
  .sum { font-size: 12.5px; color: #64748b; }

  .net { border: 1px solid #eef2f6; border-radius: 12px; padding: 12px 14px; margin-bottom: 10px; }
  .net.paused { background: #fafafa; }
  .net-row { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
  .net-title { flex: 1; min-width: 180px; font-weight: 700; font-size: 14px; display: flex; align-items: center; gap: 8px; }
  .net-title .ident { color: #64748b; font-weight: 500; }
  .dot { width: 10px; height: 10px; border-radius: 50%; flex: none; }
  .streak { font-size: 11.5px; color: #15803d; font-weight: 600; background: #f0fdf4; border: 1px solid #bbf7d0; padding: 2px 8px; border-radius: 999px; }
  .changed { font-size: 11px; color: #b45309; font-weight: 700; }

  .seg { display: inline-flex; border: 1px solid #e2e8f0; border-radius: 999px; overflow: hidden; }
  .seg button { border: none; background: #fff; padding: 6px 12px; font-size: 12px; font-weight: 600; color: #64748b; cursor: pointer; }
  .seg button + button { border-left: 1px solid #e2e8f0; }
  .seg button.on.active { background: #16a34a; color: #fff; }
  .seg button.on.pending { background: #f59e0b; color: #fff; }
  .seg button.on.paused { background: #64748b; color: #fff; }
  .seg button:disabled { cursor: default; }

  .offers { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 10px; padding-left: 18px; }
  .ofr { display: inline-flex; align-items: center; gap: 6px; border: 1px solid #e2e8f0; background: #fff; border-radius: 999px; padding: 4px 10px 4px 6px; font-size: 12px; color: #334155; cursor: pointer; }
  .ofr svg { width: 15px; height: 15px; color: #cbd5e1; }
  .ofr.on { border-color: #fcd34d; background: #fffbeb; }
  .ofr.on svg { color: #f59e0b; fill: #f59e0b; }
  .ofr.off-offer { opacity: .55; }
  .ofr:disabled { cursor: default; opacity: .45; }
  .no-offers { font-size: 12px; color: #94a3b8; margin-top: 8px; padding-left: 18px; }

  .modal-back { position: fixed; inset: 0; background: rgba(15,23,42,.45); display: none; align-items: center; justify-content: center; z-index: 100; padding: 24px; }
  .modal-back.open { display: flex; }
  .modal { background: #fff; border-radius: 16px; padding: 24px; width: 440px; max-width: 100%; }
  .modal h2 { margin: 0 0 14px; font-size: 19px; }
  .field { margin-bottom: 14px; }
  .field-label { display: block; font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 6px; }
  .field input, .field select { width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13.5px; }
  .hint { font-size: 12px; color: #64748b; margin-top: 6px; }
  .modal-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 6px; }
</style>
</head>
<body>

<div class="topbar">
  <div class="brand">MoneyWise — Plans</div>
  <div class="topbar-right">
    <a class="nav-link" href="offers.php">Offers</a>
    <a class="nav-link" href="networks.php">Networks</a>
    <button class="btn btn-green" onclick="openNewPlan()">+ New Plan</button>
  </div>
</div>

<div class="layout">
  <div class="plan-list" id="plan-list"><div class="empty">Loading…</div></div>
  <div class="editor" id="editor"><div class="empty">Select a plan, or create one with “+ New Plan”.</div></div>
</div>

<div class="modal-back" id="new-modal" onclick="if(event.target===this) closeNewPlan()">
  <div class="modal">
    <h2>New Plan</h2>
    <div class="field">
      <label class="field-label" for="np-date">Week</label>
      <input id="np-date" type="date" oninput="showNewWeek()">
      <div class="hint" id="np-week"></div>
    </div>
    <div class="field">
      <label class="field-label" for="np-name">Name <span style="font-weight:400;color:#94a3b8;">(optional)</span></label>
      <input id="np-name" type="text" placeholder="Plan 1">
    </div>
    <div class="field">
      <label class="field-label" for="np-from">Start from</label>
      <select id="np-from"></select>
      <div class="hint">Networks and stars are filled in from here. Then change only what is different next week.</div>
    </div>
    <div class="modal-actions">
      <button class="btn btn-ghost" onclick="closeNewPlan()">Cancel</button>
      <button class="btn btn-green" onclick="createPlan()">Create Plan</button>
    </div>
  </div>
</div>

<script>
// Session expired: send the admin to log in again instead of a cryptic "not authenticated".
(function () {
  const _fetch = window.fetch;
  window.fetch = async function () {
    const r = await _fetch.apply(this, arguments);
    if (r.status === 401) { alert('Session expired. Please log in again.'); location.href = '/login.php'; }
    return r;
  };
})();
</script>
<script>
let _plans = [], _networks = [], _offers = [], _currentWeek = '', _selId = null, _draft = null, _dirty = false;

function $(id) { return document.getElementById(id); }
function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
const lc = v => (v || '').toLowerCase().trim();
const STAR = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>';

// ---- dates (YYYY-MM-DD, no time zone involved) ----
function ymdToDate(s) { const [y, m, d] = s.split('-').map(Number); return new Date(y, m - 1, d); }
function dateToYmd(d) { return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0'); }
function addDays(s, n) { const d = ymdToDate(s); d.setDate(d.getDate() + n); return dateToYmd(d); }
function mondayOf(s) { const d = ymdToDate(s); const dow = (d.getDay() + 6) % 7; d.setDate(d.getDate() - dow); return dateToYmd(d); }
function fmt(s) { return ymdToDate(s).toLocaleDateString(undefined, { day: 'numeric', month: 'short' }); }
function weekLabel(mon) { return fmt(mon) + ' – ' + fmt(addDays(mon, 6)) + ', ' + ymdToDate(mon).getFullYear(); }

// ---- current state of networks / offers ----
function netState(n) { return n.enabled === false ? 'paused' : (n.pendingSince ? 'pending' : 'active'); }
function offersOf(n) {
  const same = _networks.filter(x => lc(x.name) === lc(n.name));
  return _offers.filter(o => {
    if (lc(o.network) !== lc(n.name)) return false;
    if (lc(o.account) === lc(n.identity)) return true;
    // Offer with no exact identity match belongs to the only network of that name.
    return same.length === 1 && !same.some(x => lc(x.identity) === lc(o.account));
  });
}
function currentStateMaps() {
  const networks = {}, offers = {};
  _networks.forEach(n => { networks[n.id] = netState(n); });
  _offers.forEach(o => { offers[o.id] = !!o.priority; });
  return { networks, offers };
}

async function loadAll(keepSel) {
  const [p, n, o] = await Promise.all([fetch('plans-api.php'), fetch('networks-api.php?raw=1'), fetch('offers-api.php')]);
  const pj = await p.json();
  _plans = pj.plans || []; _currentWeek = pj.currentWeek;
  _networks = await n.json();
  _offers = await o.json();
  if (!keepSel || !_plans.some(x => x.id === _selId)) {
    const pick = _plans.find(x => x.weekStart > _currentWeek) || _plans.find(x => x.weekStart === _currentWeek) || _plans[0];
    _selId = pick ? pick.id : null;
  }
  selectPlan(_selId, true);
}

function planBadge(p) {
  if (p.weekStart === _currentWeek) return '<span class="badge b-now">This week' + (p.appliedAt ? ' · applied' : '') + '</span>';
  if (p.weekStart > _currentWeek) return '<span class="badge b-next">Upcoming</span>';
  return '<span class="badge b-past">Past</span>';
}

function renderList() {
  $('plan-list').innerHTML = _plans.length
    ? _plans.map(p => `<div class="plan-item${p.id === _selId ? ' sel' : ''}" onclick="selectPlan('${esc(p.id)}')">
        <div class="pi-name"><span>${esc(p.name)}</span>${planBadge(p)}</div>
        <div class="pi-week">${esc(weekLabel(p.weekStart))}</div>
      </div>`).join('')
    : '<div class="empty">No plans yet.</div>';
}

function selectPlan(id, force) {
  if (!force && _dirty && id !== _selId && !confirm('Discard unsaved changes to this plan?')) return;
  _selId = id; _dirty = false;
  const p = _plans.find(x => x.id === id);
  _draft = p ? JSON.parse(JSON.stringify(p)) : null;
  renderList();
  renderEditor();
}

// Consecutive planned weeks (ending at this plan's week) that keep the network Active.
function streak(netId, weekStart) {
  let k = 0, w = weekStart;
  for (;;) {
    const p = w === _draft.weekStart ? _draft : _plans.find(x => x.weekStart === w);
    if (!p || (p.networks || {})[netId] !== 'active') break;
    k++; w = addDays(w, -7);
  }
  return k;
}

function renderEditor() {
  const ed = $('editor');
  if (!_draft) { ed.innerHTML = '<div class="empty">Select a plan, or create one with “+ New Plan”.</div>'; return; }
  const past = _draft.weekStart < _currentWeek;
  const isNow = _draft.weekStart === _currentWeek;
  const q = lc(($('ed-search') || {}).value);
  const hidePaused = ($('ed-hide-paused') || {}).checked;
  const nets = _draft.networks || (_draft.networks = {});
  const stars = _draft.offers || (_draft.offers = {});
  const RANK = { active: 0, pending: 1, paused: 2 };
  const stateOf = n => nets[n.id] || netState(n);

  const list = _networks
    .filter(n => !q || [n.name, n.identity].some(v => lc(v).includes(q)) || offersOf(n).some(o => lc(o.name).includes(q) || lc(o.id).includes(q)))
    .filter(n => !hidePaused || stateOf(n) !== 'paused')
    .sort((a, b) => RANK[stateOf(a)] - RANK[stateOf(b)] || lc(a.name).localeCompare(lc(b.name)) || lc(a.identity).localeCompare(lc(b.identity)));

  const counts = { active: 0, pending: 0, paused: 0 };
  _networks.forEach(n => counts[stateOf(n)]++);
  const starCount = _offers.filter(o => stars[o.id]).length;

  const rows = list.map(n => {
    const st = stateOf(n);
    const now = netState(n);
    const k = st === 'active' ? streak(n.id, _draft.weekStart) : 0;
    const seg = ['active', 'pending', 'paused'].map(s =>
      `<button class="${s}${st === s ? ' on' : ''}" ${past ? 'disabled' : ''} onclick="setNet('${esc(n.id)}','${s}')">${s[0].toUpperCase() + s.slice(1)}</button>`).join('');
    const ofs = offersOf(n);
    const offerChips = ofs.length
      ? '<div class="offers">' + ofs.map(o => {
          const on = !!stars[o.id];
          const blocked = st !== 'active';
          return `<button class="ofr${on && !blocked ? ' on' : ''}${o.enabled === false ? ' off-offer' : ''}" ${past || blocked ? 'disabled' : ''}
                    title="${blocked ? 'Network is not Active in this plan, so its offers can\'t be starred' : (on ? 'Starred this week — click to unstar' : 'Click to star this week')}${o.enabled === false ? ' (offer is disabled)' : ''}"
                    onclick="toggleStar('${esc(o.id)}')">${STAR}${esc(o.name || o.id)}</button>`;
        }).join('') + '</div>'
      : '<div class="no-offers">No offers on this network.</div>';
    return `<div class="net${st === 'paused' ? ' paused' : ''}">
      <div class="net-row">
        <div class="net-title"><span class="dot" style="background:${esc(n.color || '#94a3b8')}"></span>${esc(n.name)}<span class="ident">· ${esc(n.identity || '—')}</span>
          ${k >= 2 ? '<span class="streak">Active ' + k + ' weeks in a row</span>' : ''}
          ${!past && st !== now ? '<span class="changed">now ' + now + ' → ' + st + '</span>' : ''}</div>
        <div class="seg">${seg}</div>
      </div>
      ${offerChips}
    </div>`;
  }).join('');

  ed.innerHTML = `
    <div class="ed-head">
      <input class="ed-name" id="ed-name" value="${esc(_draft.name)}" ${past ? 'disabled' : ''} oninput="_draft.name=this.value;markDirty()">
      <div class="ed-actions">
        ${past ? '' : `<button class="btn btn-green" id="ed-save" onclick="savePlan()" ${_dirty ? '' : 'disabled'}>Save</button>`}
        ${isNow ? '<button class="btn btn-ghost" onclick="applyNow()" title="Set networks and stars to this plan right now">Apply now</button>' : ''}
        <button class="btn btn-danger" onclick="deletePlan()">Delete</button>
      </div>
    </div>
    <div class="ed-week">${esc(weekLabel(_draft.weekStart))} (Monday to Sunday) ${planBadge(_draft)}</div>
    <div class="ed-note">${past ? 'This week has ended. Kept as history.'
      : isNow ? (_draft.appliedAt ? 'Applied on ' + new Date(_draft.appliedAt).toLocaleString() + '. Saving changes does not re-apply them; use “Apply now”.' : 'Takes effect now.')
      : 'Takes effect automatically on Monday ' + fmt(_draft.weekStart) + ' (Pakistan time). Until then nothing changes.'}</div>
    <div class="ed-tools">
      <input type="search" id="ed-search" placeholder="Search network or offer…" value="${esc(q)}" oninput="renderEditor();$('ed-search').focus();">
      <label><input type="checkbox" id="ed-hide-paused" ${hidePaused ? 'checked' : ''} onchange="renderEditor()"> Hide paused</label>
      <span class="sum">${counts.active} active · ${counts.pending} pending · ${counts.paused} paused · ${starCount} starred offers</span>
    </div>
    ${rows || '<div class="empty">No networks match.</div>'}`;
  const s = $('ed-search');
  if (s && q) { s.setSelectionRange(s.value.length, s.value.length); }
}

function markDirty() { _dirty = true; const b = $('ed-save'); if (b) b.disabled = false; }

function setNet(id, state) {
  _draft.networks[id] = state;
  // Offers of a network that isn't Active can't stay starred (same rule as the Offers page).
  if (state !== 'active') {
    const n = _networks.find(x => x.id === id);
    if (n) offersOf(n).forEach(o => { _draft.offers[o.id] = false; });
  }
  markDirty(); renderEditor();
}

function toggleStar(offerId) {
  _draft.offers[offerId] = !_draft.offers[offerId];
  markDirty(); renderEditor();
}

async function post(body) {
  const r = await fetch('plans-api.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) });
  const j = await r.json().catch(() => ({}));
  if (!r.ok) throw new Error(j.error || ('HTTP ' + r.status));
  return j;
}

async function savePlan() {
  try {
    await post({ _action: 'update', id: _draft.id, name: _draft.name, networks: _draft.networks, offers: _draft.offers });
    _dirty = false;
    await loadAll(true);
  } catch (e) { alert('Save failed: ' + e.message); }
}

async function applyNow() {
  if (_dirty && !confirm('Save your changes first and apply?')) return;
  try {
    if (_dirty) await post({ _action: 'update', id: _draft.id, name: _draft.name, networks: _draft.networks, offers: _draft.offers });
    if (!confirm('Set all networks and offer stars to this plan now?')) { await loadAll(true); return; }
    await post({ _action: 'apply', id: _draft.id });
    _dirty = false;
    await loadAll(true);
  } catch (e) { alert('Apply failed: ' + e.message); }
}

async function deletePlan() {
  if (!confirm('Delete "' + _draft.name + '"? Networks and offers are not changed.')) return;
  try {
    await post({ _action: 'delete', id: _draft.id });
    _dirty = false; _selId = null;
    await loadAll(false);
  } catch (e) { alert('Delete failed: ' + e.message); }
}

// ---- new plan ----
function openNewPlan() {
  const taken = new Set(_plans.map(p => p.weekStart));
  let w = addDays(_currentWeek || mondayOf(dateToYmd(new Date())), 7);
  while (taken.has(w)) w = addDays(w, 7);
  $('np-date').value = w;
  $('np-name').value = '';
  $('np-from').innerHTML = '<option value="">Current networks and stars (as they are right now)</option>'
    + _plans.map(p => `<option value="${esc(p.id)}">Copy ${esc(p.name)} (${esc(weekLabel(p.weekStart))})</option>`).join('');
  showNewWeek();
  $('new-modal').classList.add('open');
}
function closeNewPlan() { $('new-modal').classList.remove('open'); }
function showNewWeek() {
  const v = $('np-date').value;
  $('np-week').textContent = v ? 'Week: ' + weekLabel(mondayOf(v)) + ' (Monday to Sunday)' : '';
}
async function createPlan() {
  const v = $('np-date').value;
  if (!v) { alert('Pick a week'); return; }
  const from = $('np-from').value;
  const base = from ? _plans.find(p => p.id === from) : currentStateMaps();
  try {
    const p = await post({ _action: 'create', weekStart: mondayOf(v), name: $('np-name').value,
      networks: base.networks || {}, offers: base.offers || {} });
    closeNewPlan();
    _selId = p.id;
    await loadAll(true);
  } catch (e) { alert('Could not create plan: ' + e.message); }
}

window.addEventListener('beforeunload', e => { if (_dirty) { e.preventDefault(); e.returnValue = ''; } });
loadAll(false);
</script>
</body>
</html>
