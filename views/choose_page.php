<?php $title = 'Choose a Page'; ?>
<h1>Which Facebook Page?</h1>
<div class="card"><p>This Facebook login manages several Pages. Choose the business Page to post to:</p>
  <?php foreach ($pages as $p): ?>
    <div class="conn"><i class="fa-brands fa-facebook"></i><b><?= e($p['name']) ?></b>
      <span class="muted"><?= $p['ig'] ? 'Instagram @' . e($p['ig_name']) : 'no Instagram linked' ?></span>
      <a class="btn sm right" href="<?= e(url('connect/meta?page=' . urlencode($p['id']))) ?>">Use this Page</a></div>
  <?php endforeach; ?>
</div>
