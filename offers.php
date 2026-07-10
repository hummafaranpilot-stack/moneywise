<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>MoneyWise — Offers</title>
<style>
  * { box-sizing: border-box; }
  body {
    margin: 0; min-height: 100vh; padding: 32px 24px;
    background: #f8fafc; color: #0f172a;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  }
  .topbar { max-width: 1800px; margin: 0 auto 24px; display: flex; align-items: center; justify-content: space-between; }
  .brand { font-size: 22px; font-weight: 800; color: #16a34a; }
  .btn-add {
    background: #16a34a; color: #fff; border: none; font-weight: 700;
    padding: 10px 18px; border-radius: 8px; cursor: pointer; font-size: 13.5px;
  }
  .offers-cards-grid {
    display: grid; grid-template-columns: repeat(5, 1fr); gap: 16px;
  }
  @media (max-width: 1400px) { .offers-cards-grid { grid-template-columns: repeat(4, 1fr); } }
  @media (max-width: 1100px) { .offers-cards-grid { grid-template-columns: repeat(3, 1fr); } }
  @media (max-width: 800px)  { .offers-cards-grid { grid-template-columns: repeat(2, 1fr); } }
  @media (max-width: 520px)  { .offers-cards-grid { grid-template-columns: 1fr; } }

  /* Offer card — same visual system as the Muneeb Data tool's Offers tab */
  .offer-card {
    position: relative; background: #fff; border: 1px solid #e2e8f0; border-radius: 14px;
    padding: 16px; display: flex; flex-direction: column; gap: 12px; height: 100%;
  }
  .ofc-head { display: flex; align-items: flex-start; gap: 10px; }
  .ofc-logo {
    width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-weight: 800; font-size: 13px;
  }
  .ofc-name { font-size: 15px; font-weight: 700; }
  .ofc-sub { font-size: 11.5px; color: #64748b; margin-top: 2px; }
  .ofc-payout {
    background: #16a34a; color: #fff; border: none; font-weight: 700;
    padding: 5px 12px; border-radius: 999px; font-size: 12.5px; cursor: pointer; align-self: flex-start;
  }
  .ofc-body { display: flex; flex-direction: column; gap: 10px; flex: 1; }
  .ofc-meta { display: flex; gap: 8px; flex-wrap: wrap; }
  .ofc-cr { background: #eff6ff; color: #1e40af; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 600; }
  .ofc-internal { font-size: 11px; color: #64748b; font-style: italic; }

  .ofc-section {
    display: flex; flex-direction: column; gap: 4px;
    padding: 10px; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;
  }
  .ofc-section-k { font-size: 10px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.06em; }
  .ofc-identity { display: flex; flex-direction: column; gap: 2px; }
  .ofc-id-name { font-size: 13px; font-weight: 700; }
  .ofc-id-link { font-size: 11.5px; color: #16a34a; text-decoration: none; font-family: 'SF Mono', monospace; }
  .ofc-id-tg { font-size: 11px; color: #0088cc; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; width: fit-content; }
  .ofc-id-tg svg { width: 12px; height: 12px; }

  .ofc-mw-link { display: flex; align-items: center; gap: 8px; padding: 8px 10px; background: #f0fdf4; border-radius: 8px; border: 1px solid #bbf7d0; }
  .ofc-mw-link-k { font-size: 9.5px; font-weight: 700; color: #16a34a; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 2px; }
  .ofc-mw-link-k.dest { color: #92400e; }
  .ofc-mw-link-text { min-width: 0; font-size: 11.5px; font-family: 'SF Mono', monospace; color: #166534; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  .ofc-mw-link-text.dest { color: #92400e; }
  .ofc-mw-link-dest { background: #fef3c7; border-color: #fcd34d; }
  .ofc-mw-copy { background: #fff; border: 1px solid #bbf7d0; cursor: pointer; padding: 5px; border-radius: 6px; color: #166534; flex-shrink: 0; display: flex; align-items: center; justify-content: center; }
  .ofc-mw-copy.dest { border-color: #fcd34d; color: #92400e; }
  .ofc-mw-copy svg { width: 13px; height: 13px; }

  .ofc-restrictions { display: flex; flex-wrap: wrap; gap: 4px; margin-top: auto; }
  .ofc-restrict { background: #fef2f2; color: #b91c1c; border: 1px solid #fca5a5; padding: 2px 8px; border-radius: 999px; font-size: 10.5px; font-weight: 600; }
  .ofc-restrict.empty { background: #f0fdf4; color: #166534; border-color: #bbf7d0; }

  .ofc-controls { display: flex; align-items: center; justify-content: space-between; }
  .ofc-controls-left { display: flex; align-items: center; gap: 10px; }
  .ofc-actions { display: flex; gap: 4px; flex-shrink: 0; }
  .ofc-actions button { background: rgba(255,255,255,0.95); border: 1px solid #e2e8f0; cursor: pointer; padding: 6px; border-radius: 7px; color: #64748b; }
  .ofc-actions button:hover { background: #eff6ff; color: #16a34a; border-color: #93c5fd; }
  .ofc-actions button.del:hover { background: #fee2e2; color: #b91c1c; border-color: #fca5a5; }
  .ofc-actions svg { width: 13px; height: 13px; }

  /* Sections (Priority / Today / This Week / This Month / Older) */
  .offers-section { max-width: 1800px; margin: 0 auto 22px; }
  .offers-section-head {
    display: flex; align-items: center; gap: 12px;
    padding: 10px 16px; margin-bottom: 10px;
    background: linear-gradient(135deg, #eff6ff, #faf5ff);
    border: 1px solid #c7d2fe; border-radius: 10px;
  }
  .offers-section-title { font-size: 14px; font-weight: 800; color: #1e3a8a; }
  .offers-section-sub { font-size: 11.5px; color: #64748b; font-weight: 500; margin-top: 2px; }
  .offers-section-count { margin-left: auto; background: #16a34a; color: #fff; padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 800; }
  .offers-section.priority-section .offers-section-head { background: linear-gradient(135deg, #fffbeb, #fef3c7); border-color: #fcd34d; }
  .offers-section.priority-section .offers-section-title { color: #92400e; }
  .offers-section.priority-section .offers-section-count { background: #f59e0b; }
  .offers-section-star { width: 34px; height: 34px; flex-shrink: 0; border-radius: 9px; background: linear-gradient(135deg, #fbbf24, #f59e0b); color: #fff; display: inline-flex; align-items: center; justify-content: center; }
  .offers-section-star svg { width: 18px; height: 18px; }

  /* Star + enable/disable toggle on each card */
  .ofc-star { flex-shrink: 0; background: none; border: none; cursor: pointer; color: #cbd5e1; padding: 2px; display: inline-flex; align-items: center; }
  .ofc-star:hover { color: #f59e0b; }
  .ofc-star.on { color: #f59e0b; }
  .ofc-star.on svg { fill: #f59e0b; }
  .ofc-star svg { width: 20px; height: 20px; }
  .ofc-toggle { flex-shrink: 0; position: relative; width: 34px; height: 20px; border: none; border-radius: 999px; cursor: pointer; padding: 0; background: #cbd5e1; }
  .ofc-toggle.on { background: #16a34a; }
  .ofc-toggle-knob { position: absolute; top: 2px; left: 2px; width: 16px; height: 16px; background: #fff; border-radius: 50%; transition: transform 0.16s; }
  .ofc-toggle.on .ofc-toggle-knob { transform: translateX(14px); }
  .offer-card.offer-disabled { opacity: 0.55; filter: grayscale(0.5); }
  .ofc-group-badge { background: #16a34a; color: #fff; padding: 1px 7px; border-radius: 999px; font-size: 10px; font-weight: 800; margin-left: 6px; }

  /* Modal */
  .modal-back { position: fixed; inset: 0; background: rgba(15,23,42,.45); display: none; align-items: center; justify-content: center; z-index: 100; padding: 24px; }
  .modal-back.open { display: flex; }
  .modal { background: #fff; border-radius: 16px; padding: 28px; width: 720px; max-width: 100%; max-height: 92vh; overflow-y: auto; }
  .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
  .modal-header h2 { margin: 0; font-size: 20px; }
  .btn-close { background: none; border: none; cursor: pointer; color: #64748b; }
  .field { margin-bottom: 14px; }
  .field-label { display: block; font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 6px; }
  .field-label .req { color: #ef4444; }
  .field-label .hint { font-weight: 400; color: #94a3b8; font-size: 11px; }
  .field input { width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; }
  .row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
  .modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 8px; }
  .btn-secondary { background: #f1f5f9; border: 1px solid #cbd5e1; padding: 9px 16px; border-radius: 8px; cursor: pointer; font-weight: 600; }
  .btn-primary { background: #16a34a; color: #fff; border: none; padding: 9px 18px; border-radius: 8px; cursor: pointer; font-weight: 700; }

  .grid-empty-msg { text-align: center; padding: 60px; color: #94a3b8; }
</style>
</head>
<body>

<div class="topbar">
  <div class="brand">MoneyWise — Offers</div>
  <button class="btn-add" onclick="openOfferModal()">+ Add Offer</button>
</div>

<div id="grid" style="max-width:1800px;margin:0 auto;"></div>

<!-- ADD/EDIT MODAL -->
<div class="modal-back" id="offer-modal" onclick="if(event.target===this) closeOfferModal()">
  <div class="modal">
    <div class="modal-header">
      <h2 id="offer-modal-title">Add Offer</h2>
      <button class="btn-close" onclick="closeOfferModal()">✕</button>
    </div>
    <div class="field">
      <label class="field-label" for="of-name">Offer Name<span class="req">*</span></label>
      <input id="of-name" type="text" placeholder="e.g. Lulutox">
    </div>
    <div class="field">
      <label class="field-label" for="of-id">Unique ID<span class="req">*</span> <span class="hint">(used in moneywise2026.com/&lt;id&gt;)</span></label>
      <input id="of-id" type="text" placeholder="e.g. nuubu" style="font-family:monospace;">
    </div>
    <div class="field">
      <label class="field-label" for="of-link">Destination Link<span class="req">*</span> <span class="hint">(where MoneyWise redirects traffic)</span></label>
      <input id="of-link" type="text" placeholder="https://tracker.com/offer/..." style="font-family:monospace;font-size:13px;">
    </div>
    <div class="row-2">
      <div class="field">
        <label class="field-label" for="of-network">Network</label>
        <input id="of-network" type="text" placeholder="e.g. Dr.Cash SNP">
      </div>
      <div class="field">
        <label class="field-label" for="of-internal-name">Internal / Display Name</label>
        <input id="of-internal-name" type="text">
      </div>
    </div>
    <div class="row-2">
      <div class="field">
        <label class="field-label" for="of-payout">Payout (PO)</label>
        <input id="of-payout" type="text" placeholder="$46">
      </div>
      <div class="field">
        <label class="field-label" for="of-cr">Conversion Rate (CR)</label>
        <input id="of-cr" type="text" placeholder="6-12%+">
      </div>
    </div>
    <div class="field">
      <label class="field-label" for="of-account">Account Name</label>
      <input id="of-account" type="text">
    </div>
    <div class="row-2">
      <div class="field">
        <label class="field-label" for="of-account-email">Account Email</label>
        <input id="of-account-email" type="email">
      </div>
      <div class="field">
        <label class="field-label" for="of-account-telegram">Telegram Username</label>
        <input id="of-account-telegram" type="text">
      </div>
    </div>
    <div class="row-2">
      <div class="field">
        <label class="field-label" for="of-offer-tracker">Offer Tracker</label>
        <input id="of-offer-tracker" type="text">
      </div>
      <div class="field">
        <label class="field-label" for="of-merchant">Merchant / Gateway</label>
        <input id="of-merchant" type="text">
      </div>
    </div>
    <div class="row-2">
      <div class="field">
        <label class="field-label" for="of-tracker">Our Tracker</label>
        <input id="of-tracker" type="text" value="BeMob">
      </div>
      <div class="field">
        <label class="field-label" for="of-tracker-email">Tracker Account Email</label>
        <input id="of-tracker-email" type="email">
      </div>
    </div>
    <div class="field">
      <label class="field-label" for="of-restrictions">Restrictions <span class="hint">(comma-separated)</span></label>
      <input id="of-restrictions" type="text" placeholder="e.g. Discover not accepted">
    </div>
    <div class="modal-actions">
      <button class="btn-secondary" onclick="closeOfferModal()">Cancel</button>
      <button class="btn-primary" id="of-submit-btn" onclick="submitOfferForm()">Save Offer</button>
    </div>
  </div>
</div>

<!-- MONEYWISE LINK POPUP -->
<div class="modal-back" id="mw-link-modal" onclick="if(event.target===this) closeMoneyWiseLinkModal()">
  <div class="modal" style="width:480px;">
    <div class="modal-header">
      <h2>Drive traffic to this link</h2>
      <button class="btn-close" onclick="closeMoneyWiseLinkModal()">✕</button>
    </div>
    <div class="field">
      <label class="field-label">MoneyWise Link</label>
      <input id="mw-link-value" type="text" readonly style="font-weight:600;font-family:monospace;">
    </div>
    <div class="modal-actions">
      <button class="btn-secondary" onclick="closeMoneyWiseLinkModal()">Close</button>
      <button class="btn-primary" onclick="copyMoneyWiseLink()">Copy Link</button>
    </div>
  </div>
</div>

<script>
const MONEYWISE_DOMAIN = 'https://moneywise2026.com';
let _offersCache = [];
let _editingOfferId = null;

function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
function $(id) { return document.getElementById(id); }

const PALETTE = ['#16a34a','#2563eb','#dc2626','#d97706','#7c3aed','#0891b2','#db2777'];
function colorFor(id) {
  let h = 0;
  for (let i = 0; i < id.length; i++) h = (h * 31 + id.charCodeAt(i)) >>> 0;
  return PALETTE[h % PALETTE.length];
}

async function loadOffers() {
  const r = await fetch('offers-api.php');
  _offersCache = await r.json();
  render();
}

const STAR_ICON = '<span class="offers-section-star"><svg viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg></span>';

function render() {
  const grid = $('grid');
  if (!_offersCache.length) {
    grid.innerHTML = '<div class="grid-empty-msg">No offers yet — click "+ Add Offer" to create one.</div>';
    return;
  }

  const groupKey = o => (o.name || '').toLowerCase().trim() || o.id;
  const nameSize = new Map();
  for (const o of _offersCache) nameSize.set(groupKey(o), (nameSize.get(groupKey(o)) || 0) + 1);

  const ts = o => Date.parse(o.createdAt || o.updatedAt || '') || 0;
  const startOfToday = new Date(); startOfToday.setHours(0, 0, 0, 0);
  const todayMs = startOfToday.getTime();
  const weekAgoMs = todayMs - 6 * 86400000;
  const monthAgoMs = todayMs - 29 * 86400000;

  const priorityOffers = [], bucketToday = [], bucketWeek = [], bucketMonth = [], bucketOlder = [];
  for (const o of _offersCache) {
    if (o.priority) { priorityOffers.push(o); continue; }
    const t = ts(o);
    if (t >= todayMs) bucketToday.push(o);
    else if (t >= weekAgoMs) bucketWeek.push(o);
    else if (t >= monthAgoMs) bucketMonth.push(o);
    else bucketOlder.push(o);
  }

  function renderSection(title, subtitle, items, opts) {
    opts = opts || {};
    if (!items.length) return '';
    const sorted = [...items].sort((a, b) => ts(b) - ts(a));
    const cards = sorted.map(o => {
      const gk = groupKey(o);
      const total = nameSize.get(gk) || 1;
      const badge = total > 1 ? '<span class="ofc-group-badge">×' + total + '</span>' : '';
      return renderCard(o, badge);
    }).join('');
    return `
      <div class="offers-section${opts.headClass ? ' ' + opts.headClass : ''}">
        <div class="offers-section-head">
          ${opts.icon || ''}
          <div><div class="offers-section-title">${esc(title)}</div><div class="offers-section-sub">${esc(subtitle)}</div></div>
          <span class="offers-section-count">${items.length}</span>
        </div>
        <div class="offers-cards-grid">${cards}</div>
      </div>
    `;
  }

  grid.innerHTML = ''
    + renderSection('Priority', 'Starred offers', priorityOffers, { headClass: 'priority-section', icon: STAR_ICON })
    + renderSection('🟢 Today', 'Added today', bucketToday)
    + renderSection('🗓 This Week', 'Added in the last 7 days', bucketWeek)
    + renderSection('📅 This Month', 'Added in the last 30 days', bucketMonth)
    + renderSection('🗂 Older', 'Older than 30 days', bucketOlder);
}

function renderCard(o, groupBadge) {
  const restrictions = (o.restrictions && o.restrictions.length)
    ? o.restrictions.map(r => '<span class="ofc-restrict">' + esc(r) + '</span>').join('')
    : '<span class="ofc-restrict empty">No restrictions</span>';
  const initials = (o.name || '?').trim().slice(0, 2).toUpperCase();
  const tg = (o.accountTelegram || '').replace(/^@/, '');
  const accent = colorFor(o.id);
  const disabled = o.enabled === false;
  return `
    <div class="offer-card${disabled ? ' offer-disabled' : ''}">
      <div class="ofc-head">
        <div class="ofc-logo" style="background:${accent};">${esc(initials)}</div>
        <div style="flex:1;min-width:0;">
          <div class="ofc-name">${esc(o.name)}${groupBadge || ''}</div>
          ${o.network ? '<div class="ofc-sub">' + esc(o.network) + '</div>' : ''}
        </div>
        <button class="ofc-payout">${esc(o.payout || 'Set CPA')}</button>
      </div>
      <div class="ofc-controls">
        <div class="ofc-controls-left">
          <button class="ofc-toggle${disabled ? '' : ' on'}" onclick="toggleOfferEnabled('${esc(o.id)}')" title="${disabled ? 'Disabled — click to enable' : 'Enabled — click to disable'}"><span class="ofc-toggle-knob"></span></button>
          <button class="ofc-star${o.priority ? ' on' : ''}" onclick="toggleOfferPriority('${esc(o.id)}')" title="${o.priority ? 'Remove from Priority' : 'Mark as Priority'}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg></button>
        </div>
        <div class="ofc-actions">
          <button onclick="showMoneyWiseLink('${esc(o.id)}')" title="View MoneyWise Link"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg></button>
          <button onclick="editOffer('${esc(o.id)}')" title="Edit"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button>
          <button class="del" onclick="deleteOffer('${esc(o.id)}')" title="Delete"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg></button>
        </div>
      </div>
      <div class="ofc-body">
        ${(o.conversionRate || o.internalName) ? '<div class="ofc-meta">' + (o.conversionRate ? '<span class="ofc-cr">CR ' + esc(o.conversionRate) + '</span>' : '') + (o.internalName ? '<span class="ofc-internal">' + esc(o.internalName) + '</span>' : '') + '</div>' : ''}
        <div class="ofc-section">
          <div class="ofc-section-k">Identity</div>
          <div class="ofc-identity">
            <div class="ofc-id-name">${esc(o.account || '—')}</div>
            ${o.accountEmail ? '<a href="mailto:' + esc(o.accountEmail) + '" class="ofc-id-link">' + esc(o.accountEmail) + '</a>' : ''}
            ${tg ? '<a href="https://t.me/' + esc(tg) + '" target="_blank" class="ofc-id-tg">@' + esc(tg) + '</a>' : ''}
          </div>
        </div>
        <div class="ofc-mw-link">
          <div style="flex:1;min-width:0;">
            <div class="ofc-mw-link-k">Traffic Link</div>
            <div class="ofc-mw-link-text">moneywise2026.com/${esc(o.id)}</div>
          </div>
          <button class="ofc-mw-copy" onclick="copyOfferCardLink('${esc(o.id)}', this)" title="Copy MoneyWise link"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg></button>
        </div>
        <div class="ofc-mw-link ofc-mw-link-dest">
          <div style="flex:1;min-width:0;">
            <div class="ofc-mw-link-k dest">Destination URL</div>
            <div class="ofc-mw-link-text dest">${esc(o.link || '—')}</div>
          </div>
          ${o.link ? '<button class="ofc-mw-copy dest" onclick="copyDestLink(\'' + esc(o.id) + '\', this)" title="Copy destination link"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg></button>' : ''}
        </div>
        <div class="ofc-restrictions">${restrictions}</div>
      </div>
    </div>
  `;
}

function openOfferModal(offer) {
  _editingOfferId = offer ? offer.id : null;
  $('offer-modal-title').textContent = offer ? 'Edit Offer' : 'Add Offer';
  $('of-name').value = offer ? (offer.name || '') : '';
  $('of-id').value = offer ? (offer.id || '') : '';
  $('of-id').readOnly = !!offer;
  $('of-link').value = offer ? (offer.link || '') : '';
  $('of-network').value = offer ? (offer.network || '') : '';
  $('of-internal-name').value = offer ? (offer.internalName || '') : '';
  $('of-payout').value = offer ? (offer.payout || '') : '';
  $('of-cr').value = offer ? (offer.conversionRate || '') : '';
  $('of-account').value = offer ? (offer.account || '') : '';
  $('of-account-email').value = offer ? (offer.accountEmail || '') : '';
  $('of-account-telegram').value = offer ? (offer.accountTelegram || '') : '';
  $('of-offer-tracker').value = offer ? (offer.offerTracker || '') : '';
  $('of-merchant').value = offer ? (offer.merchant || '') : '';
  $('of-tracker').value = offer ? (offer.tracker || 'BeMob') : 'BeMob';
  $('of-tracker-email').value = offer ? (offer.trackerEmail || '') : '';
  $('of-restrictions').value = offer && offer.restrictions ? offer.restrictions.join(', ') : '';
  $('offer-modal').classList.add('open');
}

function closeOfferModal() { $('offer-modal').classList.remove('open'); _editingOfferId = null; }

async function submitOfferForm() {
  const name = $('of-name').value.trim();
  if (!name) { alert('Offer name required'); return; }
  const id = $('of-id').value.trim().toLowerCase().replace(/[^a-z0-9-]/g, '');
  if (!id) { alert('Unique ID required'); return; }
  const link = $('of-link').value.trim();
  if (!link) { alert('Destination link required'); return; }

  const payload = {
    _action: _editingOfferId ? 'update' : 'create',
    id, link, name,
    network: $('of-network').value.trim(),
    internalName: $('of-internal-name').value.trim(),
    payout: $('of-payout').value.trim(),
    conversionRate: $('of-cr').value.trim(),
    account: $('of-account').value.trim(),
    accountEmail: $('of-account-email').value.trim(),
    accountTelegram: $('of-account-telegram').value.trim().replace(/^@/, ''),
    offerTracker: $('of-offer-tracker').value.trim(),
    merchant: $('of-merchant').value.trim(),
    tracker: $('of-tracker').value.trim(),
    trackerEmail: $('of-tracker-email').value.trim(),
    restrictions: $('of-restrictions').value.split(',').map(s => s.trim()).filter(Boolean),
  };

  try {
    const r = await fetch('offers-api.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
    if (!r.ok) { const e = await r.json().catch(() => ({})); throw new Error(e.error || ('HTTP ' + r.status)); }
    closeOfferModal();
    await loadOffers();
    showMoneyWiseLink(id);
  } catch (e) { alert('Save failed: ' + e.message); }
}

function editOffer(id) {
  const o = _offersCache.find(x => x.id === id);
  if (o) openOfferModal(o);
}

async function deleteOffer(id) {
  if (!confirm('Delete this offer?')) return;
  await fetch('offers-api.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ _action: 'delete', id }) });
  await loadOffers();
}

async function toggleOfferPriority(id) {
  const o = _offersCache.find(x => x.id === id);
  if (!o) return;
  const next = !o.priority;
  o.priority = next; render(); // optimistic
  try {
    const r = await fetch('offers-api.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ _action: 'update', id, priority: next }) });
    if (!r.ok) throw new Error('HTTP ' + r.status);
  } catch (e) { o.priority = !next; render(); alert('Failed: ' + e.message); }
}

async function toggleOfferEnabled(id) {
  const o = _offersCache.find(x => x.id === id);
  if (!o) return;
  const next = o.enabled === false; // currently disabled -> enable
  o.enabled = next; render(); // optimistic
  try {
    const r = await fetch('offers-api.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ _action: 'update', id, enabled: next }) });
    if (!r.ok) throw new Error('HTTP ' + r.status);
  } catch (e) { o.enabled = !next; render(); alert('Failed: ' + e.message); }
}

function showMoneyWiseLink(offerId) {
  $('mw-link-value').value = MONEYWISE_DOMAIN + '/' + offerId;
  $('mw-link-modal').classList.add('open');
}
function closeMoneyWiseLinkModal() { $('mw-link-modal').classList.remove('open'); }
function copyMoneyWiseLink() {
  const inp = $('mw-link-value'); inp.select();
  navigator.clipboard.writeText(inp.value).catch(() => document.execCommand('copy'));
}
function copyOfferCardLink(offerId, btn) {
  navigator.clipboard.writeText(MONEYWISE_DOMAIN + '/' + offerId).catch(() => {});
  _flashCopyIcon(btn);
}
function copyDestLink(offerId, btn) {
  const o = _offersCache.find(x => x.id === offerId);
  if (!o || !o.link) return;
  navigator.clipboard.writeText(o.link).catch(() => {});
  _flashCopyIcon(btn);
}
function _flashCopyIcon(btn) {
  if (!btn) return;
  const original = btn.innerHTML;
  btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
  setTimeout(() => { btn.innerHTML = original; }, 1200);
}

loadOffers();
</script>
</body>
</html>
