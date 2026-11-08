<?php $title = 'Dashboard'; ?>
<div class="row"><h1><?= e($client['name'] ?? 'All clients') ?></h1>
  <a class="btn right" href="<?= e(url('posts/edit' . ($cid ? '?client=' . $cid : ''))) ?>"><i class="fa-solid fa-plus"></i> New post</a></div>

<div class="kpis">
  <div class="kpi ok"><b><?= $stats['posted'] ?></b><span>Posted in the last 7 days</span></div>
  <div class="kpi"><b><?= $stats['scheduled'] ?></b><span>Scheduled</span></div>
  <div class="kpi warn"><b><?= $stats['pending'] ?></b><span>Awaiting approval</span></div>
  <div class="kpi bad"><b><?= $stats['failed'] ?></b><span>Need attention</span></div>
</div>

<?php if ($client): ?>
<div class="card"><h2>Connected accounts</h2>
  <?php foreach (['facebook' => 'meta', 'instagram' => 'meta', 'tiktok' => 'tiktok'] as $pl => $flow): $c = $conns[$pl] ?? null; ?>
    <div class="conn"><i class="fa-brands fa-<?= $pl ?>"></i><b><?= PLATFORMS[$pl] ?></b>
      <span class="muted"><?= $c ? e($c['account_name']) : 'Not connected' ?></span>
      <?php if (is_admin()): ?>
        <a class="btn sm <?= $c ? 'ghost' : '' ?> right" href="<?= e(url('connect/' . $flow . '?client=' . $client['id'])) ?>"><?= $c ? 'Reconnect' : 'Connect' ?></a>
      <?php else: ?>
        <span class="tag <?= $c ? 'posted' : 'pending' ?> right"><?= $c ? 'Connected' : 'Being set up' ?></span>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
  <?php if (!is_admin()): ?><p class="muted" style="font-size:13px">Your BoostMe account manager connects and looks after these accounts for you.</p><?php endif; ?>
</div>
<?php endif; ?>

<?php if ($clients): ?>
<div class="card"><h2>Clients</h2><table>
  <tr><th>Client</th><th>Accounts</th><th>Queued</th><th></th></tr>
  <?php foreach ($clients as $c): ?>
    <tr><td><b><?= e($c['name']) ?></b><br><span class="muted"><?= e($c['industry']) ?></span></td>
      <td><?= (int) $c['conns'] ?>/3</td><td><?= (int) $c['queued'] ?></td>
      <td><a class="btn sm ghost" href="<?= e(url('dashboard?client=' . $c['id'])) ?>">Dashboard</a>
          <a class="btn sm ghost" href="<?= e(url('clients/edit?id=' . $c['id'])) ?>">Settings</a></td></tr>
  <?php endforeach; ?>
</table></div>
<?php endif; ?>

<div class="grid2">
  <div class="card"><h2>Coming up</h2><table>
    <?php foreach ($upcoming as $p) { $res = []; include __DIR__ . '/_post_row.php'; } ?>
    <?php if (!$upcoming): ?><tr><td class="muted">Nothing scheduled yet.</td></tr><?php endif; ?>
  </table></div>
  <div class="card"><h2>Recently posted</h2><table>
    <?php foreach ($recent as $p) { $res = []; include __DIR__ . '/_post_row.php'; } ?>
    <?php if (!$recent): ?><tr><td class="muted">No posts yet.</td></tr><?php endif; ?>
  </table>
  <p><a href="<?= e(url('posts' . ($cid && is_admin() ? '?client=' . $cid : ''))) ?>">All posts →</a></p></div>
</div>
