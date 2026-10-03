<?php

declare(strict_types=1);

namespace App;

use App\Support\Format;
use Throwable;

// Renders the plain PHP templates in views/.
//
// Inside a template:
//   - every key of $data is a variable: ['capsule' => ...] gives $capsule;
//   - $view is this object, for the helpers below: $view->e(...), $view->icon('lock'), ...
//   - dates are printed with $view->time(...); public/js/app.js then shows them in the
//     visitor's own time zone.
class View
{
    // Inline Lucide icons (https://lucide.dev), copied from docs/ui-reference/markup.html.
    private const ICONS = [
        'hourglass' => '<path d="M5 22h14"/><path d="M5 2h14"/><path d="M17 22v-4.172a2 2 0 0 0-.586-1.414L12 12l-4.414 4.414A2 2 0 0 0 7 17.828V22"/><path d="M7 2v4.172a2 2 0 0 0 .586 1.414L12 12l4.414-4.414A2 2 0 0 0 17 6.172V2"/>',
        'log-out' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/>',
        'plus' => '<path d="M5 12h14"/><path d="M12 5v14"/>',
        'lock' => '<rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
        'lock-open' => '<rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 9.9-1"/>',
        'calendar' => '<rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/>',
        'paperclip' => '<path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"/>',
        'arrow-left' => '<path d="m12 19-7-7 7-7"/><path d="M19 12H5"/>',
        'trash' => '<path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/>',
        'download' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/>',
        'bell' => '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>',
    ];

    public function __construct(
        private readonly string $directory,
        private readonly Auth $auth,
        private readonly Session $session,
        private readonly Csrf $csrf,
        private readonly Config $config,
    ) {
    }

    // Send a full page: views/{template}.php inside views/layout.php.
    // $data must contain 'title' and may contain 'nav' ('home' | 'wall' | 'notifications').
    public function render(string $template, array $data = [], int $status = 200): void
    {
        http_response_code($status);
        $content = $this->partial($template, $data);
        echo $this->partial('layout', $this->layoutData() + $data + ['content' => $content, 'nav' => null]);
    }

    // Render one template (or a partial such as "partials/capsule-card") to a string.
    public function partial(string $template, array $data = []): string
    {
        $view = $this;
        extract($data, EXTR_SKIP); // EXTR_SKIP: never overwrite $view or $template

        ob_start();
        require $this->directory . '/' . $template . '.php';
        return (string) ob_get_clean();
    }

    // Escape text for HTML. Use it for EVERY value printed in a view.
    public function e(?string $text): string
    {
        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }

    public function icon(string $name): string
    {
        return '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"'
            . ' stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
            . self::ICONS[$name]
            . '</svg>';
    }

    // A UTC timestamp from the database as a <time> element:
    // <time data-local datetime="2027-01-01T10:00:00Z">1 Jan 2027, 10:00 UTC</time>
    public function time(string $timestamp): string
    {
        return '<time data-local datetime="' . $this->e(Format::iso($timestamp)) . '">'
            . $this->e(Format::dateTime($timestamp))
            . '</time>';
    }

    // Hidden input to put inside every <form method="post">.
    public function csrfField(): string
    {
        return '<input type="hidden" name="_token" value="' . $this->e($this->csrf->token()) . '">';
    }

    // ---------- form field helpers ($errors = ['field' => 'message']) ----------

    public function fieldClass(array $errors, string $name): string
    {
        return isset($errors[$name]) ? 'field has-error' : 'field';
    }

    // Extra attributes for an <input>: marks it invalid and links the error/hint text to it.
    public function fieldAria(array $errors, string $name, bool $hasHint = false): string
    {
        $describedBy = [];
        if ($hasHint) {
            $describedBy[] = $name . '-hint';
        }
        if (isset($errors[$name])) {
            $describedBy[] = $name . '-error';
        }

        $html = isset($errors[$name]) ? ' aria-invalid="true"' : '';
        if ($describedBy) {
            $html .= ' aria-describedby="' . $this->e(implode(' ', $describedBy)) . '"';
        }
        return $html;
    }

    public function fieldError(array $errors, string $name): string
    {
        if (!isset($errors[$name])) {
            return '';
        }
        return '<span class="error" id="' . $this->e($name) . '-error">' . $this->e($errors[$name]) . '</span>';
    }

    // Variables that every page layout needs.
    private function layoutData(): array
    {
        // If the database is down, still render the page (e.g. the 500 page) without a user.
        try {
            $user = $this->auth->user();
        } catch (Throwable) {
            $user = null;
        }

        return [
            'user' => $user,
            'authEnabled' => $this->auth->enabled(),
            'flashes' => $this->session->takeFlashes(),
            'hostname' => (string) gethostname(),
            'dbHost' => $this->config->getString('DB_HOST', 'localhost'),
            'uploadDir' => $this->config->getString('UPLOAD_DIR', 'storage/uploads'),
        ];
    }
}
