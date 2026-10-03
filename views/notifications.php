<?php // Notifications. Variables: $notifications (plain rows from the database) ?>
<div class="page-head">
  <div>
    <h1>Notifications</h1>
    <p class="subtitle">Emails the app would have sent. Nothing is delivered yet.</p>
  </div>
</div>

<?php if ($notifications): ?>
<div class="card table-card">
  <table>
    <thead>
      <tr><th scope="col">Date</th><th scope="col">To</th><th scope="col">Message</th></tr>
    </thead>
    <tbody>
<?php foreach ($notifications as $notification): ?>
      <tr>
        <td class="nowrap"><?= $view->time($notification['created_at']) ?></td>
        <td><?= $view->e($notification['recipient']) ?></td>
        <td><?= $view->e($notification['message']) ?></td>
      </tr>
<?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php else: ?>
<div class="card empty">
  <?= $view->icon('bell') ?>
  <h2>No notifications yet</h2>
  <p>They appear when you create a capsule and when it opens.</p>
</div>
<?php endif; ?>
