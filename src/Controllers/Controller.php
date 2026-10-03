<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\RedirectException;
use App\View;

// Shared helpers for the page controllers.
abstract class Controller
{
    public function __construct(protected readonly View $view)
    {
    }

    protected function render(string $template, array $data = [], int $status = 200): void
    {
        $this->view->render($template, $data, $status);
    }

    // Stops the request; App::run() sends the redirect.
    protected function redirect(string $path): never
    {
        throw new RedirectException($path);
    }
}
