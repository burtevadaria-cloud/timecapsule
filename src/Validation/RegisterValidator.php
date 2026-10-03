<?php

declare(strict_types=1);

namespace App\Validation;

use App\Repositories\UserRepository;

// Validates the "Sign up" form.
class RegisterValidator
{
    public function __construct(private readonly UserRepository $users)
    {
    }

    // Returns [$data, $errors]:
    //   $data   - ['email' => lower-cased email, 'password' => ...]
    //   $errors - ['field' => 'error message'], empty when everything is fine
    public function validate(array $input): array
    {
        $data = [
            'email' => strtolower(trim((string) ($input['email'] ?? ''))),
            'password' => (string) ($input['password'] ?? ''),
        ];
        $confirmation = (string) ($input['password_confirmation'] ?? '');
        $errors = [];

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Enter a valid email address.';
        } elseif ($this->users->findByEmail($data['email']) !== null) {
            $errors['email'] = 'This email is already registered.';
        }

        if (strlen($data['password']) < 8) {
            $errors['password'] = 'Password must be at least 8 characters.';
        }

        if ($data['password'] !== $confirmation) {
            $errors['password_confirmation'] = 'Passwords do not match.';
        }

        return [$data, $errors];
    }
}
