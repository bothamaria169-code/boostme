<?php $title = 'My account'; ?>
<h1>My account</h1>
<form class="card" method="post" style="max-width:420px"><?= csrf_field() ?>
  <p><b><?= e(user()['name']) ?></b><br><span class="muted"><?= e(user()['email']) ?></span></p>
  <label>Current password</label><input type="password" name="current" required>
  <label>New password</label><input type="password" name="new" required minlength="8">
  <p><button class="btn">Change password</button></p>
</form>
