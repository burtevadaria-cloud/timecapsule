<?php // Sign up. Variables: $email, $errors ?>
<div class="auth-card">
  <div class="page-head">
    <div>
      <h1>Create an account</h1>
      <p class="subtitle">Start sending messages to the future.</p>
    </div>
  </div>
  <form class="card" method="post" action="/register" novalidate>
    <?= $view->csrfField() ?>
    <div class="<?= $view->fieldClass($errors, 'email') ?>">
      <label for="email">Email</label>
      <input type="email" id="email" name="email" autocomplete="email" required value="<?= $view->e($email) ?>"<?= $view->fieldAria($errors, 'email') ?>>
      <?= $view->fieldError($errors, 'email') ?>
    </div>
    <div class="<?= $view->fieldClass($errors, 'password') ?>">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" autocomplete="new-password" required<?= $view->fieldAria($errors, 'password', true) ?>>
      <span class="hint" id="password-hint">At least 8 characters.</span>
      <?= $view->fieldError($errors, 'password') ?>
    </div>
    <div class="<?= $view->fieldClass($errors, 'password_confirmation') ?>">
      <label for="password_confirmation">Confirm password</label>
      <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required<?= $view->fieldAria($errors, 'password_confirmation') ?>>
      <?= $view->fieldError($errors, 'password_confirmation') ?>
    </div>
    <button type="submit" class="btn btn-primary btn-block">Sign up</button>
    <p class="form-footer">Already have an account? <a href="/login">Log in</a></p>
  </form>
</div>
