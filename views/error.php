<?php // Error page (403 / 404 / 500). Variables: $code, $heading, $text, $details ?>
<div class="card error-page">
  <p class="error-code"><?= (int) $code ?></p>
  <h1><?= $view->e($heading) ?></h1>
  <p class="subtitle"><?= $view->e($text) ?></p>
<?php if (!empty($details)): ?>
  <pre><?= $view->e($details) ?></pre>
<?php endif; ?>
  <div class="actions center">
    <a class="btn btn-secondary" href="/">Go home</a>
  </div>
</div>
