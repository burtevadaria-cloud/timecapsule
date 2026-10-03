<?php

declare(strict_types=1);

namespace App\Models;

// One row of the "users" table.
class User
{
    public function __construct(
        public readonly int $id,
        public readonly string $email,
        public readonly string $passwordHash,
    ) {
    }

    public static function fromRow(array $row): self
    {
        return new self((int) $row['id'], $row['email'], $row['password_hash']);
    }

    public function checkPassword(string $password): bool
    {
        // Also false for the guest user: its hash "!" is not a real bcrypt hash.
        return password_verify($password, $this->passwordHash);
    }
}
