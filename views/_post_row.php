<?php
/** One post as a table row. Needs $p; optional $res (results by platform). */
$media = json_decode($p['media'], true) ?: [];
$first = $media[0] ?? '';
$res = $res ?? [];
$icons = ['facebook' => 'fa-brands fa-facebook', 'instagram' => 'fa-brands fa-instagram', 'tiktok' => 'fa-brands fa-tiktok'];
?>
<tr>
  <td><?php if ($p['media_type'] === 'video'): ?><video class="thumb" src="<?= e(media_url($first)) ?>" muted preload="metadata"></video>
      <?php elseif ($first): ?><img class="thumb" src="<?= e(media_url($first)) ?>" alt="" loading="lazy"><?php endif; ?></td>
  <td>
    <?php if (is_admin() && empty($cid)): ?><b><?= e($p['client_name']) ?></b><br><?php endif; ?>
    <?= e(mb_strimwidth($p['caption'], 0, 110, '…')) ?>
    <?php foreach ($res as $r): if (!$r['ok']): ?><div style="color:var(--bad);font-size:12px"><?= e(PLATFORMS[$r['platform']] . ': ' . $r['error']) ?></div><?php endif; endforeach; ?>
  </td>
  <td style="white-space:nowrap">
    <?php foreach (explode(',', $p['platforms']) as $pl): $r = $res[$pl] ?? null; ?>
      <?php if ($r && $r['link']): ?><a href="<?= e($r['link']) ?>" target="_blank"><?php endif; ?>
      <i class="plat <?= $icons[$pl] ?? '' ?>" title="<?= e(PLATFORMS[$pl] ?? $pl) ?><?= $r ? ($r['ok'] ? ' ✓' : ' ✗') : '' ?>" style="<?= $r && !$r['ok'] ? 'opacity:.35' : '' ?>"></i>
      <?php if ($r && $r['link']): ?></a><?php endif; ?>
    <?php endforeach; ?>
  </td>
  <td style="white-space:nowrap"><?= e(when($p['scheduled_at'])) ?></td>
  <td><span class="tag <?= e($p['status']) ?>"><?= e($p['status'] === 'pending' ? 'awaiting approval' : $p['status']) ?></span></td>
  <td style="white-space:nowrap">
    <?php if (in_array($p['status'], ['pending', 'scheduled'], true) && (is_admin() || $p['status'] === 'pending')): ?>
      <a class="btn sm ghost" href="<?= e(url('posts/edit?id=' . $p['id'])) ?>">Edit</a>
    <?php endif; ?>
    <?php $act = static fn($do, $label, $cls = '') => '<form method="post" action="' . e(url('posts/action')) . '" style="display:inline">' . csrf_field()
        . '<input type="hidden" name="id" value="' . (int) $p['id'] . '"><input type="hidden" name="do" value="' . $do . '"><button class="btn sm ' . $cls . '"'
        . ($do === 'delete' ? ' onclick="return confirm(\'Delete this post?\')"' : '') . '>' . $label . '</button></form>'; ?>
    <?php if (is_admin()): ?>
      <?= $p['status'] === 'pending' ? $act('approve', 'Approve') : '' ?>
      <?= in_array($p['status'], ['pending', 'scheduled'], true) ? $act('now', 'Post now', 'dark') : '' ?>
      <?= in_array($p['status'], ['failed', 'partial'], true) ? $act('retry', 'Retry failed', 'dark') : '' ?>
      <?= $p['status'] !== 'posting' ? $act('delete', '<i class="fa-solid fa-trash"></i>', 'red') : '' ?>
    <?php elseif ($p['status'] === 'pending'): ?>
      <?= $act('delete', '<i class="fa-solid fa-trash"></i>', 'red') ?>
    <?php endif; ?>
  </td>
</tr>
