<?php $title = 'Posts'; $q = $cid && is_admin() ? 'client=' . $cid . '&' : ''; ?>
<div class="row"><h1>Posts</h1>
  <a class="btn right" href="<?= e(url('posts/edit' . ($cid ? '?client=' . $cid : ''))) ?>"><i class="fa-solid fa-plus"></i> New post</a></div>
<div class="row" style="margin-bottom:14px">
  <?php foreach (['' => 'All', 'pending' => 'Awaiting approval', 'scheduled' => 'Scheduled', 'posted' => 'Posted', 'failed' => 'Failed', 'partial' => 'Partly posted'] as $s => $label): ?>
    <a class="btn sm <?= $status === $s ? '' : 'ghost' ?>" href="<?= e(url('posts?' . $q . 'status=' . $s)) ?>"><?= $label ?></a>
  <?php endforeach; ?>
</div>
<div class="card"><table>
  <tr><th></th><th>Post</th><th>Where</th><th>When</th><th>Status</th><th></th></tr>
  <?php foreach ($posts as $p) { $res = $results[$p['id']] ?? []; include __DIR__ . '/_post_row.php'; } ?>
  <?php if (!$posts): ?><tr><td colspan="6" class="muted">No posts here.</td></tr><?php endif; ?>
</table></div>
