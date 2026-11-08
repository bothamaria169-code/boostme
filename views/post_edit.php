<?php
$title = $post ? 'Edit post' : 'New post';
$chosen = $post ? explode(',', $post['platforms']) : array_keys($conns ?: PLATFORMS);
$when = $post['scheduled_at'] ?? date('Y-m-d H:00:00', strtotime('+1 hour'));
$media = json_decode($post['media'] ?? '[]', true) ?: [];
?>
<h1><?= $title ?></h1>
<form class="card" method="post" enctype="multipart/form-data"><?= csrf_field() ?>
  <?php if (is_admin()): ?>
    <label>Client</label>
    <select name="client_id" required onchange="location='?client='+this.value">
      <option value="">— choose —</option>
      <?php foreach ($clients as $c): ?><option value="<?= $c['id'] ?>" <?= $c['id'] == $cid ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
    </select>
  <?php endif; ?>

  <label>Photos or video</label>
  <input type="file" name="media[]" multiple accept="image/jpeg,image/png,image/webp,video/mp4,video/quicktime" <?= $media ? '' : 'required' ?>>
  <p class="muted" style="font-size:13px;margin:4px 0">Up to 10 photos (carousel) <b>or</b> one video. Best for hair: vertical 9:16 video, 15–60 s, MP4.
    <?= $media ? 'Leave empty to keep the current ' . count($media) . ' file(s).' : '' ?></p>
  <?php if ($media): ?><div class="row"><?php foreach ($media as $m): ?>
    <?= str_ends_with($m, '.jpg') ? '<img class="thumb" src="' . e(media_url($m)) . '">' : '<video class="thumb" src="' . e(media_url($m)) . '"></video>' ?>
  <?php endforeach; ?></div><?php endif; ?>

  <label>Caption</label>
  <textarea name="caption" maxlength="2100" placeholder="e.g. Fresh knotless braids for the weekend ✨ Which length would you go for?"><?= e($post['caption'] ?? '') ?></textarea>
  <p class="muted" style="font-size:13px;margin:4px 0">Your saved hashtags and booking line are added automatically if the caption has none.</p>

  <label>Post to</label>
  <?php foreach (PLATFORMS as $k => $label): ?>
    <span class="check"><input type="checkbox" name="platforms[]" value="<?= $k ?>" <?= in_array($k, $chosen, true) ? 'checked' : '' ?>>
      <i class="fa-brands fa-<?= $k ?>"></i> <?= $label ?><?= $cid && empty($conns[$k]) ? ' <small class="muted">(not connected)</small>' : '' ?></span>
  <?php endforeach; ?>

  <label>Date and time</label>
  <input type="datetime-local" name="scheduled_at" value="<?= e(date('Y-m-d\TH:i', strtotime($when))) ?>" required style="max-width:260px">

  <p style="margin-top:18px"><button class="btn"><?= is_admin() ? 'Schedule post' : 'Send for approval' ?></button>
    <a href="<?= e(url('posts')) ?>" class="btn ghost">Cancel</a></p>
</form>
