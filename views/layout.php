<?php
$u = user();
$flashes = $_SESSION['flash'] ?? [];
unset($_SESSION['flash']);
$here = trim((string) ($_GET['r'] ?? ''), '/');
$nav = static fn(string $r, string $icon, string $label) => '<a href="' . e(url($r)) . '" class="' . (str_starts_with($here ?: 'dashboard', $r) ? 'on' : '') . '"><i class="fa-solid ' . $icon . '"></i> ' . $label . '</a>';
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>BoostMe<?= isset($title) ? ' · ' . e($title) : '' ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;600&family=Manrope:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="<?= e(url('assets/app.css')) ?>">
</head>
<body class="<?= $u ? 'app' : 'guest' ?>">
<?php if ($u): ?>
<aside class="side">
  <div class="brand"><i class="fa-solid fa-rocket"></i> Boost<b>Me</b></div>
  <nav>
    <?= $nav('dashboard', 'fa-gauge', 'Dashboard') ?>
    <?= $nav('posts', 'fa-images', 'Posts') ?>
    <a href="<?= e(url('posts/edit')) ?>"><i class="fa-solid fa-plus"></i> New post</a>
    <?php if (is_admin()): ?>
      <?= $nav('clients', 'fa-users', 'Clients') ?>
      <?= $nav('settings', 'fa-gear', 'Settings') ?>
    <?php endif; ?>
    <?= $nav('account', 'fa-user', 'My account') ?>
    <a href="<?= e(url('guide')) ?>" target="_blank"><i class="fa-solid fa-book-open"></i> User guide</a>
    <a href="<?= e(url('logout')) ?>"><i class="fa-solid fa-right-from-bracket"></i> Log out</a>
  </nav>
  <div class="who"><?= e($u['name']) ?><small><?= is_admin() ? 'Admin' : e(client((int) $u['client_id'])['name'] ?? '') ?></small></div>
</aside>
<?php endif; ?>
<main>
  <?php foreach ($flashes as [$type, $msg]): ?>
    <div class="flash <?= e($type) ?>"><?= e($msg) ?></div>
  <?php endforeach; ?>
  <?= $content ?>
</main>
</body>
</html>
