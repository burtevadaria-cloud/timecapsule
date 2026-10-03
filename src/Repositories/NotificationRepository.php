<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database;

// All SQL for the "notifications" table.
// Notifications are only listed in a table, so plain arrays are enough here.
class NotificationRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    public function create(int $capsuleId, int $userId, string $recipient, string $message): void
    {
        $this->db->query(
            'INSERT INTO notifications (capsule_id, user_id, recipient, message) VALUES (?, ?, ?, ?)',
            [$capsuleId, $userId, $recipient, $message]
        );
    }

    // Newest first. Each row has: id, capsule_id, user_id, recipient, message, created_at.
    public function forUser(int $userId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT 100',
            [$userId]
        );
    }
}
