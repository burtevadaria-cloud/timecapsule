<?php // New capsule form. Variables: $old, $errors, $maxUploadMb ?>
<div class="narrow">
  <a class="back-link" href="/">
    <?= $view->icon('arrow-left') ?>
    Back to my capsules
  </a>
  <div class="page-head">
    <h1>New capsule</h1>
  </div>
  <form class="card" method="post" action="/capsules" enctype="multipart/form-data" novalidate>
    <?= $view->csrfField() ?>

    <div class="<?= $view->fieldClass($errors, 'title') ?>">
      <label for="title">Title</label>
      <input type="text" id="title" name="title" maxlength="120" required value="<?= $view->e($old['title']) ?>"<?= $view->fieldAria($errors, 'title') ?>>
      <?= $view->fieldError($errors, 'title') ?>
    </div>

    <div class="<?= $view->fieldClass($errors, 'message') ?>">
      <label for="message">Message</label>
      <textarea id="message" name="message" maxlength="5000" required<?= $view->fieldAria($errors, 'message') ?>><?= $view->e($old['message']) ?></textarea>
      <?= $view->fieldError($errors, 'message') ?>
    </div>

    <?php // "open_at" is the visitor's local time; public/js/app.js adds the same moment in UTC as "open_at_utc". ?>
    <div class="<?= $view->fieldClass($errors, 'open_at') ?>">
      <label for="open_at">Open at</label>
      <input type="datetime-local" id="open_at" name="open_at" required value="<?= $view->e($old['open_at']) ?>" data-datetime-picker<?= $view->fieldAria($errors, 'open_at', true) ?>>
      <input type="hidden" name="open_at_utc" value="">
      <span class="hint" id="open_at-hint">Date and time in your time zone.</span>
      <?= $view->fieldError($errors, 'open_at') ?>
    </div>

    <div class="<?= $view->fieldClass($errors, 'recipient_email') ?>">
      <label for="recipient_email">Recipient email <span class="optional">(optional)</span></label>
      <input type="email" id="recipient_email" name="recipient_email" value="<?= $view->e($old['recipient_email']) ?>"<?= $view->fieldAria($errors, 'recipient_email', true) ?>>
      <span class="hint" id="recipient_email-hint">We will notify this address when the capsule opens. Leave empty to notify yourself.</span>
      <?= $view->fieldError($errors, 'recipient_email') ?>
    </div>

    <div class="<?= $view->fieldClass($errors, 'attachment') ?>">
      <label for="attachment">Attachment <span class="optional">(optional)</span></label>
      <input type="file" id="attachment" name="attachment" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf"<?= $view->fieldAria($errors, 'attachment', true) ?>>
      <span class="hint" id="attachment-hint">JPG, PNG, GIF, WEBP or PDF, up to <?= (int) $maxUploadMb ?> MB.</span>
      <?= $view->fieldError($errors, 'attachment') ?>
    </div>

    <div class="field">
      <label class="checkbox">
        <input type="checkbox" name="is_public" value="1"<?= $old['is_public'] ? ' checked' : '' ?>>
        Show on the public wall after opening
      </label>
    </div>

    <button type="submit" class="btn btn-primary btn-block">
      <?= $view->icon('lock') ?>
      Seal capsule
    </button>
  </form>
</div>
