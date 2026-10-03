<?php
// Public wall. Variables: $capsules (each one has ownerEmail filled in)
use App\Support\Format;
?>
<div class="page-head">
  <h1>Public wall</h1>
</div>

<?php if ($capsules): ?>
<div class="grid">
<?php foreach ($capsules as $capsule): ?>
<?= $view->partial('partials/capsule-card', ['capsule' => $capsule, 'author' => Format::authorName($capsule->ownerEmail)]) ?>
<?php endforeach; ?>
</div>
<?php else: ?>
<div class="card empty">
  <?= $view->icon('hourglass') ?>
  <h2>The wall is empty</h2>
  <p>Public capsules appear here once they open.</p>
</div>
<?php endif; ?>
