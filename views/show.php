<?php
// One capsule. Variables: $capsule (App\Models\Capsule), $isOwner
use App\Support\Format;

$id = $capsule->id;
$hasFile = $capsule->hasFile();
?>
<div class="narrow">
<?php if ($isOwner): ?>
  <a class="back-link" href="/">
    <?= $view->icon('arrow-left') ?>
    Back to my capsules
  </a>
<?php else: ?>
  <a class="back-link" href="/wall">
    <?= $view->icon('arrow-left') ?>
    Back to the wall
  </a>
<?php endif; ?>

<?php if (!$capsule->isOpened()): ?>
  <div class="card sealed-view">
    <div class="seal">
      <?= $view->icon('lock') ?>
    </div>
    <h1><?= $view->e($capsule->title) ?></h1>
    <p class="subtitle">This capsule is sealed until <?= $view->time($capsule->openAt) ?>.</p>
    <p class="countdown"><?= $view->e(Format::countdown($capsule->openAt)) ?></p>
<?php if ($isOwner): ?>
    <div class="actions center">
      <?= $view->partial('partials/delete-form', ['id' => $id]) ?>
    </div>
<?php endif; ?>
  </div>
<?php else: ?>
  <article class="card">
    <span class="badge badge-opened">
      <?= $view->icon('lock-open') ?>
      Opened
    </span>
    <h1 style="margin-top:8px"><?= $view->e($capsule->title) ?></h1>
    <p class="subtitle">Sealed on <?= $view->time($capsule->createdAt) ?> · opened on <?= $view->time($capsule->openedAt) ?></p>
    <div class="letter"><?= $view->e($capsule->message) ?></div>
<?php if ($hasFile && $capsule->isImage()): ?>

    <img class="attachment-image" src="/capsules/<?= $id ?>/file" alt="Attachment: <?= $view->e($capsule->fileName) ?>">
<?php endif; ?>
<?php if ($hasFile || $isOwner): ?>

    <div class="actions">
<?php if ($hasFile): ?>
      <a class="btn btn-secondary" href="/capsules/<?= $id ?>/file?download=1">
        <?= $view->icon('download') ?>
        Download <?= $view->e($capsule->fileName) ?>
      </a>
<?php endif; ?>
<?php if ($isOwner): ?>
      <?= $view->partial('partials/delete-form', ['id' => $id]) ?>
<?php endif; ?>
    </div>
<?php endif; ?>
  </article>
<?php endif; ?>
</div>
