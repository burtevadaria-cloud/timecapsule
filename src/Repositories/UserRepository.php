<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database;
use App\Models\User;

// All SQL for the "users" table.
class UserRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    public function find(int $id): ?User
    {
        $row = $this->db->fetchOne('SELECT id, email, password_hash FROM users WHERE id = ?', [$id]);
        return $row === null ? null : User::fromRow($row);
    }

    public function findByEmail(string $email): ?User
    {
        $row = $this->db->fetchOne('SELECT id, email, password_hash FROM users WHERE email = ?', [$email]);
        return $row === null ? null : User::fromRow($row);
    }

    public function create(string $email, string $password): User
    {
        // password_hash() uses bcrypt and adds a random salt. We never store the password itself.
        $row = $this->db->fetchOne(
            'INSERT INTO users (email, password_hash) VALUES (?, ?) RETURNING id, email, password_hash',
            [$email, password_hash($password, PASSWORD_BCRYPT)]
        );
        return User::fromRow($row);
    }
}
