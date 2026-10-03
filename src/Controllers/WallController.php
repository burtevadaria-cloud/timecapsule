<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\CapsuleRepository;
use App\View;

// The public wall: opened public capsules from everybody. No login needed.
class WallController extends Controller
{
    public function __construct(View $view, private readonly CapsuleRepository $capsules)
    {
        parent::__construct($view);
    }

    // GET /wall
    public function index(): void
    {
        $this->render('wall', [
            'title' => 'Public wall',
            'nav' => 'wall',
            'capsules' => $this->capsules->onWall(),
        ]);
    }
}
