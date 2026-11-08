<?php $title = 'Clients'; ?>
<h1>Clients</h1>
<div class="card"><table>
  <tr><th>Name</th><th>Industry</th><th>Email</th><th>Posts</th><th></th></tr>
  <?php foreach ($clients as $c): ?>
    <tr style="<?= $c['active'] ? '' : 'opacity:.5' ?>"><td><b><?= e($c['name']) ?></b></td><td><?= e($c['industry']) ?></td>
      <td><?= e($c['email']) ?></td><td><?= (int) $c['posts'] ?></td>
      <td><a class="btn sm ghost" href="<?= e(url('dashboard?client=' . $c['id'])) ?>">Dashboard</a>
          <a class="btn sm" href="<?= e(url('clients/edit?id=' . $c['id'])) ?>">Manage</a></td></tr>
  <?php endforeach; ?>
</table></div>
<form class="card" method="post"><?= csrf_field() ?><h2>Add a client</h2>
  <div class="grid2"><div><label>Business name</label><input name="name" required></div>
  <div><label>Industry</label><input name="industry" placeholder="e.g. Hair salon"></div></div>
  <p><button class="btn">Add client</button></p>
</form>
