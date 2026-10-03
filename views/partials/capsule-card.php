<?php
// One capsule card (used on "My capsules" and on the public wall).
// Variables: $capsule (App\Models\Capsule), $author (email local part, only on the wall).
use App\Support\Format;

$opened = $capsule->isOpened();
?>
<a class="card capsule-card<?= $opened ? ' is-opened' : '' ?>" href="/capsules/<?= $capsule->id ?>">
<?php if ($opened): ?>
  <span class="badge badge-opened">
    <?= $view->icon('lock-open') ?>
    Opened
  </span>
<?php else: ?>
  <span class="badge badge-sealed">
    <?= $view->icon('lock') ?>
    Sealed
  </span>
<?php endif; ?>
  <h2><?= $view->e($capsule->title) ?></h2>
<?php if ($opened): ?>
  <p class="excerpt"><?= $view->e(Format::excerpt($capsule->message)) ?></p>
<?php endif; ?>
  <div class="capsule-meta">
    <span>
      <?= $view->icon('calendar') ?>
      <?= $view->time($capsule->openAt) ?>
    </span>
<?php if (!$opened): ?>
    <span><?= $view->e(Format::countdown($capsule->openAt)) ?></span>
<?php endif; ?>
<?php if ($capsule->hasFile()): ?>
    <span>
      <?= $view->icon('paperclip') ?>
      1 file
    </span>
<?php endif; ?>
<?php if (isset($author)): ?>
    <span>by <?= $view->e($author) ?></span>
<?php endif; ?>
  </div>
</a>
