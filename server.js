/**
 * MoneyWise redirect service.
 *
 * moneywise2026.com/{offerId}?any=params  →  302 redirect to the mapped
 * offer's real tracking link, with all incoming query params merged in.
 *
 * offers-map.json ({ offerId: link }) is synced automatically from the
 * Muneeb Data admin tool (tools/muneeb-data/server.js) whenever an offer's
 * link is saved — this file just reads it and redirects.
 */

const http = require('http');
const fs = require('fs');
const path = require('path');

const PORT = process.env.PORT || 3000;
const MAP_FILE = path.join(__dirname, 'offers-map.json');

function readMap() {
  try {
    return JSON.parse(fs.readFileSync(MAP_FILE, 'utf8') || '{}');
  } catch (_) { return {}; }
}

const server = http.createServer((req, res) => {
  const u = new URL(req.url, 'http://x');

  if (u.pathname === '/health') {
    res.writeHead(200, { 'Content-Type': 'application/json' });
    return res.end(JSON.stringify({ ok: true }));
  }

  const offerId = decodeURIComponent(u.pathname.replace(/^\//, '')).split('/')[0];

  if (!offerId) {
    res.writeHead(200, { 'Content-Type': 'text/plain' });
    return res.end('MoneyWise redirect service is running.');
  }

  const map = readMap();
  const dest = map[offerId];

  if (!dest) {
    res.writeHead(404, { 'Content-Type': 'text/plain' });
    return res.end('Offer not found: ' + offerId);
  }

  let destUrl;
  try {
    destUrl = new URL(dest);
  } catch (_) {
    res.writeHead(500, { 'Content-Type': 'text/plain' });
    return res.end('Offer link is misconfigured: ' + offerId);
  }

  // Incoming click params (fbclid, sub-ids, etc.) merge into the dest link,
  // overriding any static placeholder of the same name.
  u.searchParams.forEach((value, key) => destUrl.searchParams.set(key, value));

  res.writeHead(302, { Location: destUrl.toString() });
  res.end();
});

server.listen(PORT, () => console.log('MoneyWise redirect service listening on port ' + PORT));
