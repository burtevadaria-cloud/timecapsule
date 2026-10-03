<?php
// Page layout: header, flash messages, page content ($content), footer.
// Variables: $title, $nav ('home' | 'wall' | 'notifications' | null), $content,
// and from View: $user, $authEnabled, $flashes, $hostname, $dbHost, $uploadDir.

$showPrivateNav = !$authEnabled || $user !== null;

$navLink = function (string $key, string $href, string $label) use ($nav, $view): string {
    $current = $nav === $key ? ' aria-current="page"' : '';
    return '<a href="' . $view->e($href) . '"' . $current . '>' . $view->e($label) . '</a>';
};
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $view->e($title) ?> · TimeCapsule</title>
  <link rel="stylesheet" href="/vendor/flatpickr/flatpickr.min.css">
  <link rel="stylesheet" href="/css/app.css">
  <script src="/vendor/flatpickr/flatpickr.min.js" defer></script>
  <script src="/js/app.js" defer></script>
</head>
<body>

<header class="site-header">
  <div class="container">
    <a class="logo" href="/">
      <?= $view->icon('hourglass') ?>
      TimeCapsule
    </a>
    <nav class="nav" aria-label="Main">
<?php if ($showPrivateNav): ?>
      <?= $navLink('home', '/', 'My capsules') ?>
<?php endif; ?>
      <?= $navLink('wall', '/wall', 'Public wall') ?>
<?php if ($showPrivateNav): ?>
      <?= $navLink('notifications', '/notifications', 'Notifications') ?>
<?php endif; ?>
    </nav>
    <div class="user-box">
<?php if (!$authEnabled): ?>
      <span>Guest</span>
<?php elseif ($user !== null): ?>
      <span><?= $view->e($user->email) ?></span>
      <form method="post" action="/logout">
        <?= $view->csrfField() ?>
        <button type="submit" class="btn-link">
          <?= $view->icon('log-out') ?>
          Log out
        </button>
      </form>
<?php else: ?>
      <a class="btn btn-secondary" href="/login">Log in</a>
      <a class="btn btn-primary" href="/register">Sign up</a>
<?php endif; ?>
    </div>
  </div>
</header>

<main>
  <div class="container">
<?php foreach ($flashes as $flash): ?>
<?php if ($flash['type'] === 'error'): ?>
    <div class="flash flash-error" role="alert"><?= $view->e($flash['message']) ?></div>
<?php else: ?>
    <div class="flash flash-success" role="status"><?= $view->e($flash['message']) ?></div>
<?php endif; ?>
<?php endforeach; ?>

<?= $content ?>
  </div>
</main>

<footer class="site-footer">
  <div class="container">
    <?php // Which machine answered, and where the data and uploaded files are kept. ?>
    Served by <code><?= $view->e($hostname) ?></code> · DB: <code><?= $view->e($dbHost) ?></code> · Files: <code>local disk (<?= $view->e($uploadDir) ?>)</code><br>
    <strong>Daria Burteva</strong>
  </div>
</footer>

</body>
</html>
