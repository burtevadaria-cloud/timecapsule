<?php
// Front controller: EVERY request (except static files like /css/app.css or /js/app.js) ends up here.
// Apache: "FallbackResource /index.php"; nginx: "try_files $uri /index.php";
// PHP built-in server: serves existing files itself and sends everything else here.

declare(strict_types=1);

// "php -S localhost:8080 public/index.php" (index.php used as router script): let the
// built-in server send existing files (css, js, flatpickr) itself.
if (PHP_SAPI === 'cli-server') {
    $file = realpath(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    if ($file !== false && is_file($file) && $file !== __FILE__) {
        return false;
    }
}

// Composer's autoloader loads our classes (App\... from src/) and the libraries in vendor/.
require dirname(__DIR__) . '/vendor/autoload.php';

$app = new App\App(dirname(__DIR__));
$app->run();
