<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database;
use App\Models\Capsule;

// All SQL for the "capsules" table.
class CapsuleRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    public function find(int $id): ?Capsule
    {
        $row = $this->db->fetchOne('SELECT * FROM capsules WHERE id = ?', [$id]);
        return $row === null ? null : Capsule::fromRow($row);
    }

    /** @return Capsule[] */
    public function forUser(int $userId): array
    {
        $rows = $this->db->fetchAll(
            'SELECT * FROM capsules WHERE user_id = ? ORDER BY open_at ASC, id ASC',
            [$userId]
        );
        return array_map([Capsule::class, 'fromRow'], $rows);
    }

    /**
     * Opened public capsules from all users, newest first.
     *
     * @return Capsule[]
     */
    public function onWall(): array
    {
        $rows = $this->db->fetchAll(
            'SELECT c.*, u.email AS owner_email
               FROM capsules c
               JOIN users u ON u.id = c.user_id
              WHERE c.opened_at IS NOT NULL AND c.is_public
              ORDER BY c.opened_at DESC
              LIMIT 50'
        );
        return array_map([Capsule::class, 'fromRow'], $rows);
    }

    /**
     * Mark every due capsule as opened and return the rows that were changed
     * (id, user_id, title, recipient_email, owner_email).
     *
     * This is ONE atomic statement: the UPDATE only returns rows it changed itself, so
     * when two requests run it at the same time, each capsule is opened (and returned)
     * only once.
     *
     * @return array<int, array<string, mixed>>
     */
    public function openDue(string $now): array
    {
        return $this->db->fetchAll(
            'UPDATE capsules c
                SET opened_at = ?
               FROM users u
              WHERE u.id = c.user_id AND c.opened_at IS NULL AND c.open_at <= ?
             RETURNING c.id, c.user_id, c.title, c.recipient_email, u.email AS owner_email',
            [$now, $now]
        );
    }

    // Insert a capsule and return it as it is stored in the database.
    // $data keys: user_id, title, message, open_at_utc ("Y-m-d H:i:s", UTC), is_public, recipient_email,
    // file_path, file_name, file_mime.
    public function create(array $data): Capsule
    {
        $row = $this->db->fetchOne(
            'INSERT INTO capsules (user_id, title, message, open_at, is_public, recipient_email, file_path, file_name, file_mime)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
             RETURNING *',
            [
                $data['user_id'],
                $data['title'],
                $data['message'],
                $data['open_at_utc'],
                $data['is_public'] ? 'true' : 'false',
                $data['recipient_email'] !== '' ? $data['recipient_email'] : null,
                $data['file_path'],
                $data['file_name'],
                $data['file_mime'],
            ]
        );
        return Capsule::fromRow($row);
    }

    // Its notifications are deleted by the database (ON DELETE CASCADE).
    public function delete(int $id): void
    {
        $this->db->query('DELETE FROM capsules WHERE id = ?', [$id]);
    }
}
