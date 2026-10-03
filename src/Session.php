<?php

declare(strict_types=1);

namespace App;

// PHP sessions: a cookie with a random id, and the data in a file in storage/sessions/.
class Session
{
    public function __construct(private readonly string $savePath)
    {
    }

    public function start(): void
    {
        session_save_path($this->savePath);
        session_name('timecapsule_session');
        session_set_cookie_params([
            'httponly' => true, // JavaScript cannot read the cookie
            'samesite' => 'Lax', // the cookie is not sent with forms posted from other sites
        ]);
        session_start();
    }

    public function isStarted(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    // A new session id (the data is kept). Call it when the user logs in or out,
    // so an id that somebody else learned before is worthless ("session fixation").
    public function regenerate(): void
    {
        session_regenerate_id(true);
    }

    // Flash messages are shown once, on the next rendered page.
    // $type is "success" or "error".
    public function flash(string $type, string $message): void
    {
        $messages = $this->get('flash', []);
        $messages[] = ['type' => $type, 'message' => $message];
        $this->set('flash', $messages);
    }

    public function takeFlashes(): array
    {
        if (!$this->isStarted()) {
            return [];
        }

        $messages = $this->get('flash', []);
        $this->remove('flash');
        return $messages;
    }
}
