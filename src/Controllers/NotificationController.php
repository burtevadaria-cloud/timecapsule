<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Repositories\NotificationRepository;
use App\View;

// The "e-mails" the app would have sent (rows written by Mailer and by CapsuleOpener).
class NotificationController extends Controller
{
    public function __construct(
        View $view,
        private readonly Auth $auth,
        private readonly NotificationRepository $notifications,
    ) {
        parent::__construct($view);
    }

    // GET /notifications
    public function index(): void
    {
        $user = $this->auth->requireUser();

        $this->render('notifications', [
            'title' => 'Notifications',
            'nav' => 'notifications',
            'notifications' => $this->notifications->forUser($user->id),
        ]);
    }
}
