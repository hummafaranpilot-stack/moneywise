<?php
/**
 * Public About / Contact page (no login), served at /about and /contact via
 * .htaccess. Fill in the business details below — they must match the
 * Payoneer profile exactly.
 */
$fullName = 'Sadaqat Ali';
$email    = 'digitalskillsbyali@gmail.com';
$phone    = '+92 3106480985';
$address  = 'Ilyas Colony Street 11, SadiqAbad, 64351, PK';

function esc($s) { return htmlspecialchars((string) $s, ENT_QUOTES); }
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>About Us — MoneyWise</title>
<style>
  * { box-sizing: border-box; }
  body {
    margin: 0; min-height: 100vh; padding: 48px 16px;
    background: #0b1220; color: #e6ebf5;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    line-height: 1.6;
  }
  .wrap { max-width: 720px; margin: 0 auto; }
  .brand { font-size: 22px; font-weight: 800; color: #22c55e; margin-bottom: 28px; }
  h1 { font-size: 30px; margin: 0 0 12px; }
  h2 { font-size: 13px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .05em; margin: 0 0 16px; }
  .intro { color: #cbd5e1; font-size: 15.5px; margin: 0 0 28px; }
  .card { background: #121a2b; border-radius: 14px; padding: 24px; margin-bottom: 16px; }
  .owner { display: flex; align-items: center; gap: 16px; }
  .owner img { width: 88px; height: 88px; flex-shrink: 0; border-radius: 50%; object-fit: cover; background: #253046; }
  .owner-name { font-size: 18px; font-weight: 700; }
  dl { margin: 0; display: grid; grid-template-columns: 90px 1fr; gap: 12px 16px; font-size: 14.5px; }
  dt { color: #94a3b8; font-weight: 600; }
  dd { margin: 0; overflow-wrap: anywhere; }
  @media (max-width: 480px) {
    dl { grid-template-columns: 1fr; gap: 2px; }
    dd { margin-bottom: 12px; }
    dd:last-child { margin-bottom: 0; }
  }
</style>
</head>
<body>
<main class="wrap">
  <div class="brand">MoneyWise</div>
  <h1>About Us</h1>
  <p class="intro">MoneyWise is run by <?= esc($fullName) ?>. We provide performance and affiliate marketing services, helping advertisers reach customers through CPA campaigns.</p>

  <section class="card">
    <h2>Owner</h2>
    <div class="owner">
      <img src="/images/owner.jpg" alt="<?= esc($fullName) ?>">
      <div class="owner-name"><?= esc($fullName) ?></div>
    </div>
  </section>

  <section class="card">
    <h2>Contact Information</h2>
    <dl>
      <dt>Name</dt><dd><?= esc($fullName) ?></dd>
      <dt>Email</dt><dd><?= esc($email) ?></dd>
      <dt>Phone</dt><dd><?= esc($phone) ?></dd>
      <dt>Address</dt><dd><?= esc($address) ?></dd>
    </dl>
  </section>
</main>
</body>
</html>
