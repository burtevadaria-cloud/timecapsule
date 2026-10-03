<?php

declare(strict_types=1);

namespace App\Models;

// One row of the "capsules" table, plus the rules about who may see it.
// Dates are strings in UTC, exactly as PostgreSQL returns them ("2027-01-01 00:00:00").
class Capsule
{
    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        public readonly string $title,
        public readonly string $message,
        public readonly string $openAt,
        public readonly bool $isPublic,
        public readonly ?string $recipientEmail,
        public readonly ?string $filePath,
        public readonly ?string $fileName,
        public readonly ?string $fileMime,
        public readonly ?string $openedAt,
        public readonly string $createdAt,
        public readonly ?string $ownerEmail = null, // only filled by queries that join "users"
    ) {
    }

    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            userId: (int) $row['user_id'],
            title: $row['title'],
            message: $row['message'],
            openAt: $row['open_at'],
            isPublic: (bool) $row['is_public'],
            recipientEmail: $row['recipient_email'],
            filePath: $row['file_path'],
            fileName: $row['file_name'],
            fileMime: $row['file_mime'],
            openedAt: $row['opened_at'],
            createdAt: $row['created_at'],
            ownerEmail: $row['owner_email'] ?? null,
        );
    }

    // opened_at is set by CapsuleOpener at the start of the first request after the open time.
    public function isOpened(): bool
    {
        return $this->openedAt !== null;
    }

    public function hasFile(): bool
    {
        return $this->filePath !== null;
    }

    public function isImage(): bool
    {
        return $this->fileMime !== null && str_starts_with($this->fileMime, 'image/');
    }

    public function isOwnedBy(?User $user): bool
    {
        return $user !== null && $user->id === $this->userId;
    }

    // Owners always see their capsule; everybody else only opened public capsules.
    public function isVisibleTo(?User $user): bool
    {
        return $this->isOwnedBy($user) || ($this->isPublic && $this->isOpened());
    }
}
