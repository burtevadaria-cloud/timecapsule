<?php

declare(strict_types=1);

namespace App;

use App\Models\Capsule;
use App\Repositories\NotificationRepository;

// Simulated e-mail.
// The app does not really send e-mails: send() waits a few seconds, like a slow mail
// server would, and then writes a row to the "notifications" table. The
// Notifications page lists these rows.
class Mailer
{
    public function __construct(
        private readonly NotificationRepository $notifications,
        private readonly float $delaySeconds,
    ) {
    }

    // "Send" an e-mail about $capsule to $recipient.
    public function send(Capsule $capsule, string $recipient, string $message): void
    {
        if ($this->delaySeconds > 0) {
            usleep((int) ($this->delaySeconds * 1_000_000)); // pretend we talk to a mail server
        }

        $this->notifications->create($capsule->id, $capsule->userId, $recipient, $message);
    }
}
