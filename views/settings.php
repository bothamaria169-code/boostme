<?php $title = 'Settings'; $s = static fn($k) => e(setting($k)); ?>
<h1>Settings</h1>
<form method="post" autocomplete="off"><?= csrf_field() ?>
<div class="grid2">
  <div class="card"><h2><i class="fa-brands fa-meta"></i> Meta app (Facebook + Instagram)</h2>
    <label>App ID</label><input name="meta_app_id" autocomplete="off" inputmode="numeric" value="<?= $s('meta_app_id') ?>">
    <label>App secret <small class="muted"><?= setting('meta_app_secret') ? '(saved — blank keeps it)' : '' ?></small></label><input type="password" name="meta_app_secret" autocomplete="new-password">
    <label>Configuration ID <small class="muted">(only for Facebook Login for Business apps)</small></label><input name="meta_config_id" autocomplete="off" inputmode="numeric" value="<?= $s('meta_config_id') ?>">
    <p class="muted" style="font-size:13px">Valid OAuth Redirect URI to add in the Meta app:<br><code><?= e(url('connect/meta')) ?></code></p>
  </div>
  <div class="card"><h2><i class="fa-brands fa-tiktok"></i> TikTok app</h2>
    <label>Client key</label><input name="tiktok_client_key" autocomplete="off" value="<?= $s('tiktok_client_key') ?>">
    <label>Client secret <small class="muted"><?= setting('tiktok_client_secret') ? '(saved — blank keeps it)' : '' ?></small></label><input type="password" name="tiktok_client_secret" autocomplete="new-password">
    <p class="muted" style="font-size:13px">Redirect URI: <code><?= e(url('connect/tiktok')) ?></code></p>
    <label>Domain check file name <small class="muted">(e.g. tiktokAbC123.txt)</small></label><input name="tiktok_verify_file" value="<?= $s('tiktok_verify_file') ?>">
    <label>Domain check file content</label><input name="tiktok_verify_content" value="<?= $s('tiktok_verify_content') ?>">
  </div>
</div>
<div class="card"><h2>Weekly reports</h2>
  <label>Report day</label>
  <select name="report_day" style="max-width:220px"><?php foreach ([1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'] as $n => $d): ?>
    <option value="<?= $n ?>" <?= setting('report_day', '1') == $n ? 'selected' : '' ?>><?= $d ?></option><?php endforeach; ?></select>
  <label>Send a test email to</label><input type="email" name="test_email" autocomplete="off" style="max-width:320px" placeholder="you@example.com">
  <p class="muted" style="font-size:13px">Mail server details are in the <code>.env</code> file (MAIL_HOST, MAIL_USER, MAIL_PASS).</p>
  <button class="btn">Save settings</button>
</div>
</form>
<div class="card"><h2>Recent emails</h2><table>
  <?php foreach ($emails as $m): ?><tr><td><?= e(when($m['sent_at'])) ?></td><td><?= e($m['recipient']) ?></td><td><?= e($m['subject']) ?></td><td><?= $m['ok'] ? '✅' : '❌ ' . e($m['error']) ?></td></tr><?php endforeach; ?>
  <?php if (!$emails): ?><tr><td class="muted">None yet.</td></tr><?php endif; ?>
</table></div>
