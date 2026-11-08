<?php $title = $client['name']; $id = (int) $client['id']; $f = static fn($k) => e($client[$k]); ?>
<div class="row"><h1><?= e($client['name']) ?></h1>
  <a class="btn ghost right" href="<?= e(url('dashboard?client=' . $id)) ?>">Open dashboard</a></div>

<div class="grid2">
<form class="card" method="post"><?= csrf_field() ?><input type="hidden" name="do" value="save">
  <h2>Details</h2>
  <label>Business name</label><input name="name" value="<?= $f('name') ?>" required>
  <label>Industry</label><input name="industry" value="<?= $f('industry') ?>">
  <div class="grid2"><div><label>Contact person</label><input name="contact_name" value="<?= $f('contact_name') ?>"></div>
    <div><label>Phone</label><input name="phone" value="<?= $f('phone') ?>"></div></div>
  <label>Email (weekly report)</label><input type="email" name="email" value="<?= $f('email') ?>">
  <label>Hashtags added to every post</label><input name="hashtags" value="<?= $f('hashtags') ?>">
  <label>Booking line added to every post</label><input name="signature" value="<?= $f('signature') ?>">
  <label>TikTok mode</label>
  <select name="tiktok_mode">
    <option value="inbox" <?= $client['tiktok_mode'] === 'inbox' ? 'selected' : '' ?>>Inbox — client taps Post in the TikTok app (works now)</option>
    <option value="direct" <?= $client['tiktok_mode'] === 'direct' ? 'selected' : '' ?>>Direct — posts by itself (needs TikTok app audit)</option>
  </select>
  <p><span class="check"><input type="checkbox" name="weekly_email" <?= $client['weekly_email'] ? 'checked' : '' ?>> Send weekly report</span>
     <span class="check"><input type="checkbox" name="active" <?= $client['active'] ? 'checked' : '' ?>> Active</span></p>
  <button class="btn">Save</button>
</form>

<div>
  <div class="card"><h2>Social accounts</h2>
    <?php foreach (['facebook' => 'meta', 'instagram' => 'meta', 'tiktok' => 'tiktok'] as $pl => $flow): $c = $conns[$pl] ?? null; ?>
      <div class="conn"><i class="fa-brands fa-<?= $pl ?>"></i><b><?= PLATFORMS[$pl] ?></b><span class="muted"><?= $c ? e($c['account_name']) : 'Not connected' ?></span>
        <span class="right"><a class="btn sm" href="<?= e(url('connect/' . $flow . '?client=' . $id)) ?>"><?= $c ? 'Reconnect' : 'Connect' ?></a>
        <?php if ($c): ?><form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="do" value="disconnect"><input type="hidden" name="platform" value="<?= $pl ?>"><button class="btn sm ghost">Remove</button></form><?php endif; ?></span>
      </div>
    <?php endforeach; ?>
    <p class="muted" style="font-size:13px">Press Connect while logged in (in this browser) to the Facebook account that manages the client's Page — or to their TikTok account. Clients only see the status.</p>
  </div>

  <div class="card"><h2>Client logins</h2>
    <?php foreach ($users as $usr): ?>
      <div class="conn"><i class="fa-solid fa-user"></i><?= e($usr['name']) ?> <span class="muted"><?= e($usr['email']) ?></span>
        <span class="right">
          <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="do" value="reset"><input type="hidden" name="user_id" value="<?= $usr['id'] ?>"><button class="btn sm ghost">Reset password</button></form>
          <form method="post" style="display:inline" onsubmit="return confirm('Remove this login?')"><?= csrf_field() ?><input type="hidden" name="do" value="remove_user"><input type="hidden" name="user_id" value="<?= $usr['id'] ?>"><button class="btn sm red"><i class="fa-solid fa-trash"></i></button></form>
        </span></div>
    <?php endforeach; ?>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="do" value="user">
      <div class="grid2"><div><label>Name</label><input name="user_name"></div><div><label>Email</label><input type="email" name="user_email" required></div></div>
      <label>Password <small class="muted">(blank = generate one)</small></label><input name="password">
      <p><button class="btn">Create login</button></p></form>
  </div>

  <div class="card"><h2>Weekly report</h2>
    <p class="muted">Sent automatically every <?= e(date('l', strtotime('Sunday +' . setting('report_day', '1') . ' days'))) ?> morning.
      Last sent: <?= e(when($client['last_report'])) ?></p>
    <a class="btn ghost" target="_blank" href="<?= e(url('report/preview?id=' . $id)) ?>">Preview</a>
    <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="do" value="report"><button class="btn">Send now</button></form>
  </div>
</div>
</div>
