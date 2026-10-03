<?php // Delete button for the capsule owner. Variables: $id ?>
<form method="post" action="/capsules/<?= (int) $id ?>/delete" onsubmit="return confirm('Delete this capsule forever?')">
  <?= $view->csrfField() ?>
  <button type="submit" class="btn btn-danger">
    <?= $view->icon('trash') ?>
    Delete
  </button>
</form>
