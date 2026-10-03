<?php

declare(strict_types=1);

namespace App;

use App\Http\RedirectException;
use App\Models\User;
use App\Repositories\UserRepository;

// Who is using the app?
//
// AUTH_ENABLED=true  -> users register and log in; the user id is kept in the session.
// AUTH_ENABLED=false -> no login at all; everybody is the built-in guest user (id 1).
class Auth
{
    public const GUEST_USER_ID = 1;

    private ?User $user = null;
    private bool $loaded = false; // the user is looked up once per request

    public function __construct(
        private readonly bool $enabled,
        private readonly Session $session,
        private readonly UserRepository $users,
    ) {
    }

    public function enabled(): bool
    {
        return $this->enabled;
    }

    // The current user, or null when nobody is logged in.
    public function user(): ?User
    {
        if (!$this->loaded) {
            $id = $this->enabled ? $this->session->get('user_id') : self::GUEST_USER_ID;
            $this->user = $id === null ? null : $this->users->find((int) $id);
            $this->loaded = true;
        }

        return $this->user;
    }

    // Pages that need a user: send anonymous visitors to the login page.
    public function requireUser(): User
    {
        return $this->user() ?? throw new RedirectException('/login');
    }

    // Login/register pages: a logged-in user has nothing to do there.
    public function requireGuest(): void
    {
        if ($this->user() !== null) {
            throw new RedirectException('/');
        }
    }

    public function login(User $user): void
    {
        $this->session->regenerate();
        $this->session->set('user_id', $user->id);
    }

    public function logout(): void
    {
        $this->session->remove('user_id');
        $this->session->regenerate();
    }
}
