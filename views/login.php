<div class="card">
  <div class="brand" style="font:600 30px Oswald;color:var(--brand)"><i class="fa-solid fa-rocket"></i> BoostMe</div>
  <h2><?= $setup ? 'Create the admin login' : 'Log in' ?></h2>
  <form method="post"><?= csrf_field() ?>
    <?php if ($setup): ?><label>Your name</label><input name="name" required><?php endif; ?>
    <label>Email</label><input type="email" name="email" required autofocus>
    <label>Password</label><input type="password" name="password" required minlength="<?= $setup ? 8 : 1 ?>">
    <p><button class="btn"><?= $setup ? 'Create admin' : 'Log in' ?></button></p>
  </form>
  <p class="muted" style="font-size:13px">Forgot your password? Ask your BoostMe account manager to reset it.</p>
</div>
