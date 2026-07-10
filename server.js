/**
 * MoneyWise redirect service.
 *
 * moneywise2026.com/{offerId}?any=params  →  validates visitor IP is US,
 * shows a branded "Redirecting…" interstitial, then forwards to the
 * mapped offer's real tracking link with all incoming query params merged in.
 * Non-US (or unresolvable) IPs are rejected with an error page — never redirected.
 *
 * offers-map.json ({ offerId: link }) is synced automatically from the
 * Muneeb Data admin tool (tools/muneeb-data/server.js) whenever an offer's
 * link is saved.
 *
 * Every hit is appended to clicks-log.json for the /stats dashboard.
 */

const http = require('http');
const https = require('https');
const fs = require('fs');
const path = require('path');

const PORT = process.env.PORT || 3000;
const MAP_FILE    = path.join(__dirname, 'offers-map.json');
const CLICKS_FILE = path.join(__dirname, 'clicks-log.json');
const STATS_HTML  = path.join(__dirname, 'stats.html');

const GEO_TIMEOUT_MS = 5000;

function readMap() {
  try { return JSON.parse(fs.readFileSync(MAP_FILE, 'utf8') || '{}'); } catch (_) { return {}; }
}

function readClicks() {
  try { return JSON.parse(fs.readFileSync(CLICKS_FILE, 'utf8') || '[]'); } catch (_) { return []; }
}

function appendClick(record) {
  try {
    const arr = readClicks();
    arr.push(record);
    fs.writeFileSync(CLICKS_FILE, JSON.stringify(arr, null, 2));
  } catch (_) {}
}

function getClientIp(req) {
  const xff = req.headers['x-forwarded-for'];
  let ip = xff ? xff.split(',')[0].trim() : req.socket.remoteAddress;
  if (ip && ip.startsWith('::ffff:')) ip = ip.slice(7);
  return ip || '';
}

/** Geo-IP lookup via ip-api.com (server-to-server, no browser CORS/HTTPS concern). */
function geoLookup(ip) {
  return new Promise((resolve) => {
    const url = `http://ip-api.com/json/${encodeURIComponent(ip)}?fields=status,message,country,countryCode,regionName,city,query`;
    const req = http.get(url, { timeout: GEO_TIMEOUT_MS }, (res) => {
      let body = '';
      res.on('data', (c) => { body += c; });
      res.on('end', () => {
        try {
          const j = JSON.parse(body);
          if (j.status !== 'success') return resolve(null);
          resolve(j);
        } catch (_) { resolve(null); }
      });
    });
    req.on('timeout', () => { req.destroy(); resolve(null); });
    req.on('error', () => resolve(null));
  });
}

function escHtml(s) {
  return String(s == null ? '' : s).replace(/[&<>"']/g, (c) => (
    { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]
  ));
}

function renderRedirectingPage({ offerId, destUrl, city, region }) {
  const location = [city, region].filter(Boolean).map(escHtml).join(', ') || 'United States';
  return `<!doctype html>
<html><head><meta charset="utf-8">
<title>MoneyWise — Redirecting…</title>
<meta http-equiv="refresh" content="1.2;url=${escHtml(destUrl)}">
<style>
  *{box-sizing:border-box;} body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;
    background:#0b1220;color:#e6ebf5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;}
  .card{text-align:center;padding:48px 40px;background:#121a2b;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.4);max-width:420px;}
  .brand{font-size:22px;font-weight:800;letter-spacing:.5px;color:#22c55e;margin-bottom:28px;}
  .spinner{width:40px;height:40px;border:3px solid rgba(255,255,255,.15);border-top-color:#22c55e;border-radius:50%;
    margin:0 auto 22px;animation:spin .8s linear infinite;}
  @keyframes spin{to{transform:rotate(360deg);}}
  h1{font-size:17px;font-weight:600;margin:0 0 8px;}
  p.sub{color:#94a3b8;font-size:13px;margin:0 0 22px;}
  .badge{display:inline-flex;align-items:center;gap:8px;background:#0f2418;border:1px solid #1e4030;color:#4ade80;
    padding:8px 16px;border-radius:999px;font-size:12.5px;font-weight:600;}
</style></head>
<body>
  <div class="card">
    <div class="brand">MoneyWise</div>
    <div class="spinner"></div>
    <h1>Redirecting you to your offer…</h1>
    <p class="sub">Please wait, this only takes a moment.</p>
    <div class="badge">✓ Verified Location — United States, ${location}</div>
  </div>
</body></html>`;
}

function renderRejectedPage({ country }) {
  const where = country ? escHtml(country) : 'an unrecognized location';
  return `<!doctype html>
<html><head><meta charset="utf-8">
<title>MoneyWise — Unable to Continue</title>
<style>
  *{box-sizing:border-box;} body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;
    background:#0b1220;color:#e6ebf5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;}
  .card{text-align:center;padding:48px 40px;background:#121a2b;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.4);max-width:440px;}
  .brand{font-size:22px;font-weight:800;letter-spacing:.5px;color:#f87171;margin-bottom:28px;}
  h1{font-size:17px;font-weight:600;margin:0 0 10px;}
  p{color:#94a3b8;font-size:13.5px;line-height:1.6;margin:0;}
</style></head>
<body>
  <div class="card">
    <div class="brand">MoneyWise</div>
    <h1>We couldn't verify your location</h1>
    <p>This link is only available to visitors in the United States. Your connection appears to originate from ${where}, so we're not able to continue — this protects both you and the offer from a potential IP mismatch.</p>
  </div>
</body></html>`;
}

function jsonResp(res, code, obj) {
  res.writeHead(code, { 'Content-Type': 'application/json' });
  res.end(JSON.stringify(obj));
}

function dateKey(iso) { return String(iso).slice(0, 10); } // YYYY-MM-DD

function buildStats(clicks, from, to) {
  const filtered = clicks.filter((c) => {
    const d = dateKey(c.ts);
    if (from && d < from) return false;
    if (to && d > to) return false;
    return true;
  });
  const byOffer = {};
  filtered.forEach((c) => {
    const key = c.offerId || '(unknown)';
    if (!byOffer[key]) byOffer[key] = { offerId: key, total: 0, valid: 0, redirected: 0 };
    byOffer[key].total++;
    if (c.valid) byOffer[key].valid++;
    if (c.redirected) byOffer[key].redirected++;
  });
  const offers = Object.values(byOffer).sort((a, b) => b.total - a.total);
  const totals = offers.reduce((acc, o) => {
    acc.total += o.total; acc.valid += o.valid; acc.redirected += o.redirected;
    return acc;
  }, { total: 0, valid: 0, redirected: 0 });
  return { offers, totals };
}

const server = http.createServer((req, res) => {
  const u = new URL(req.url, 'http://x');

  if (u.pathname === '/health') return jsonResp(res, 200, { ok: true });

  if (u.pathname === '/stats' || u.pathname === '/stats.html') {
    try {
      const html = fs.readFileSync(STATS_HTML, 'utf8');
      res.writeHead(200, { 'Content-Type': 'text/html' });
      return res.end(html);
    } catch (_) {
      res.writeHead(500, { 'Content-Type': 'text/plain' });
      return res.end('stats.html missing');
    }
  }

  if (u.pathname === '/api/stats') {
    const from = u.searchParams.get('from') || '';
    const to   = u.searchParams.get('to')   || '';
    const clicks = readClicks();
    return jsonResp(res, 200, buildStats(clicks, from, to));
  }

  const offerId = decodeURIComponent(u.pathname.replace(/^\//, '')).split('/')[0];

  if (!offerId) {
    res.writeHead(200, { 'Content-Type': 'text/plain' });
    return res.end('MoneyWise redirect service is running.');
  }

  const map = readMap();
  const dest = map[offerId];
  const ip = getClientIp(req);

  if (!dest) {
    appendClick({ ts: new Date().toISOString(), offerId, ip, country: null, region: null, city: null, valid: false, redirected: false, reason: 'offer-not-found' });
    res.writeHead(404, { 'Content-Type': 'text/plain' });
    return res.end('Offer not found: ' + offerId);
  }

  geoLookup(ip).then((geo) => {
    const isUs = !!geo && geo.countryCode === 'US';

    if (!isUs) {
      appendClick({
        ts: new Date().toISOString(), offerId, ip,
        country: geo ? geo.country : null, region: geo ? geo.regionName : null, city: geo ? geo.city : null,
        valid: false, redirected: false, reason: geo ? 'non-us-ip' : 'geo-lookup-failed',
      });
      res.writeHead(200, { 'Content-Type': 'text/html' });
      return res.end(renderRejectedPage({ country: geo ? geo.country : null }));
    }

    let destUrl;
    try { destUrl = new URL(dest); } catch (_) {
      appendClick({ ts: new Date().toISOString(), offerId, ip, country: geo.country, region: geo.regionName, city: geo.city, valid: true, redirected: false, reason: 'bad-destination-url' });
      res.writeHead(500, { 'Content-Type': 'text/plain' });
      return res.end('Offer link is misconfigured: ' + offerId);
    }
    u.searchParams.forEach((value, key) => destUrl.searchParams.set(key, value));

    appendClick({ ts: new Date().toISOString(), offerId, ip, country: geo.country, region: geo.regionName, city: geo.city, valid: true, redirected: true, reason: null });
    res.writeHead(200, { 'Content-Type': 'text/html' });
    res.end(renderRedirectingPage({ offerId, destUrl: destUrl.toString(), city: geo.city, region: geo.regionName }));
  });
});

server.listen(PORT, () => console.log('MoneyWise redirect service listening on port ' + PORT));
