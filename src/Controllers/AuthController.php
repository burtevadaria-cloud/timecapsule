<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Repositories\UserRepository;
use App\Session;
use App\Validation\RegisterValidator;
use App\View;

// Register, log in, log out. These routes exist only when AUTH_ENABLED=true.
class AuthController extends Controller
{
    public function __construct(
        View $view,
        private readonly Auth $auth,
        private readonly Session $session,
        private readonly UserRepository $users,
        private readonly RegisterValidator $validator,
    ) {
        parent::__construct($view);
    }

    // GET /login
    public function loginForm(): void
    {
        $this->auth->requireGuest();
        $this->render('login', ['title' => 'Log in', 'email' => '']);
    }

    // POST /login
    public function login(): void
    {
        $this->auth->requireGuest();

        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');

        $user = $email === '' ? null : $this->users->findByEmail($email);

        if ($user === null || !$user->checkPassword($password)) {
            // Same message for "unknown email" and "wrong password": do not reveal which emails exist.
            $this->session->flash('error', 'Invalid email or password.');
            $this->render('login', ['title' => 'Log in', 'email' => $email], 422);
            return;
        }

        $this->auth->login($user);
        $this->redirect('/');
    }

    // GET /register
    public function registerForm(): void
    {
        $this->auth->requireGuest();
        $this->render('register', ['title' => 'Sign up', 'email' => '', 'errors' => []]);
    }

    // POST /register
    public function register(): void
    {
        $this->auth->requireGuest();

        [$data, $errors] = $this->validator->validate($_POST);
        if ($errors) {
            $this->render('register', ['title' => 'Sign up', 'email' => $data['email'], 'errors' => $errors], 422);
            return;
        }

        $this->auth->login($this->users->create($data['email'], $data['password']));
        $this->session->flash('success', 'Welcome! Your account is ready.');
        $this->redirect('/');
    }

    // POST /logout
    public function logout(): void
    {
        $this->auth->requireUser();
        $this->auth->logout();
        $this->redirect('/login');
    }
}
