<?php

declare(strict_types=1);

namespace App;

use App\Http\HttpException;

// CSRF protection: every POST form contains a hidden "_token" field with a random value
// stored in the session. Another website cannot know this value, so it cannot submit
// forms on behalf of our logged-in users.
class Csrf
{
    private const SESSION_KEY = 'csrf_token';

    public function __construct(private readonly Session $session)
    {
    }

    public function token(): string
    {
        $token = $this->session->get(self::SESSION_KEY, '');
        if ($token === '') {
            $token = bin2hex(random_bytes(32));
            $this->session->set(self::SESSION_KEY, $token);
        }
        return $token;
    }

    // Called by the App before any POST handler runs.
    public function check(mixed $sentToken): void
    {
        $expected = $this->session->get(self::SESSION_KEY, '');

        // hash_equals() compares in constant time, so the token cannot be guessed
        // by measuring how long the comparison takes.
        if (!is_string($sentToken) || $expected === '' || !hash_equals($expected, $sentToken)) {
            throw new HttpException(403, 'Your session expired. Please reload the page.');
        }
    }
}
