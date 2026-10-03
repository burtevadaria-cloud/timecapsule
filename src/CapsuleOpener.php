<?php

declare(strict_types=1);

namespace App;

use App\Repositories\CapsuleRepository;
use App\Repositories\NotificationRepository;

// Opens every capsule whose open time has passed.
// App::run() calls openDue() at the start of every web request (except GET /health),
// so pages always show the current state.
class CapsuleOpener
{
    public function __construct(
        private readonly CapsuleRepository $capsules,
        private readonly NotificationRepository $notifications,
    ) {
    }

    public function openDue(): void
    {
        // One atomic UPDATE ... RETURNING (no simulated mail delay here).
        foreach ($this->capsules->openDue(gmdate('Y-m-d H:i:s')) as $row) {
            $this->notifications->create(
                (int) $row['id'],
                (int) $row['user_id'],
                $row['recipient_email'] ?? $row['owner_email'],
                sprintf('Your capsule "%s" is now open.', $row['title'])
            );
        }
    }
}
