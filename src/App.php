<?php

declare(strict_types=1);

namespace App;

use App\Controllers\AuthController;
use App\Controllers\CapsuleController;
use App\Controllers\HealthController;
use App\Controllers\NotificationController;
use App\Controllers\WallController;
use App\Http\HttpException;
use App\Http\RedirectException;
use App\Repositories\CapsuleRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\UserRepository;
use App\Validation\CapsuleValidator;
use App\Validation\RegisterValidator;
use Dotenv\Dotenv;
use Throwable;

// The application: creates every object once, passes each one the objects it needs
// ("constructor injection"), and handles web requests.
//
// Used by public/index.php (web) and by the scripts in bin/ (command line).
class App
{
    public readonly Config $config;
    public readonly Database $db;

    private Session $session;
    private Auth $auth;
    private Csrf $csrf;
    private View $view;
    private CapsuleRepository $capsules;
    private NotificationRepository $notifications;
    private UserRepository $users;

    public function __construct(private readonly string $rootDir)
    {
        // Load .env if it exists. "Immutable" = variables that are already set
        // in the real environment are never overwritten by the file.
        Dotenv::createImmutable($rootDir)->safeLoad();

        // All dates in the app and in the database are UTC.
        date_default_timezone_set('UTC');

        $this->config = new Config();
        $this->db = Database::fromConfig($this->config);

        $this->users = new UserRepository($this->db);
        $this->capsules = new CapsuleRepository($this->db);
        $this->notifications = new NotificationRepository($this->db);

        $this->session = new Session($rootDir . '/storage/sessions');
        $this->auth = new Auth($this->config->getBool('AUTH_ENABLED', true), $this->session, $this->users);
        $this->csrf = new Csrf($this->session);
        $this->view = new View($rootDir . '/views', $this->auth, $this->session, $this->csrf, $this->config);
    }

    // Handle one web request and send the response.
    public function run(): void
    {
        try {
            $this->handleRequest();
        } catch (RedirectException $e) {
            header('Location: ' . $e->location, true, 302);
        } catch (HttpException $e) {
            $this->renderError($e->status, $e->text);
        } catch (Throwable $e) {
            // Log the details for the developer, show a friendly page to the visitor.
            error_log((string) $e);
            $details = $this->config->getBool('APP_DEBUG') ? $e->getMessage() : null;
            $this->renderError(500, Database::problemHint($e), $details);
        }
    }

    private function handleRequest(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';

        // Monitoring tools call /health every few seconds: do not create a session file
        // for each of those calls.
        if ($path !== '/health') {
            $this->session->start();

            // A due capsule is opened by the next web request, before any page is rendered.
            (new CapsuleOpener($this->capsules, $this->notifications))->openDue();
        }

        $route = $this->routes()->match($method, $path);
        if ($route === null) {
            throw new HttpException(404);
        }

        if ($method === 'POST') {
            // Every form must send the CSRF token.
            $this->csrf->check($_POST['_token'] ?? '');
        }

        [$handler, $arguments] = $route;
        $handler(...$arguments);
    }

    private function routes(): Router
    {
        $capsules = new CapsuleController(
            $this->view,
            $this->auth,
            $this->session,
            $this->capsules,
            new CapsuleValidator($this->config->getInt('MAX_UPLOAD_MB', 5)),
            new Storage($this->uploadDir()),
            new Mailer($this->notifications, $this->config->getFloat('MAIL_DELAY_SECONDS', 3)),
        );
        $wall = new WallController($this->view, $this->capsules);
        $notifications = new NotificationController($this->view, $this->auth, $this->notifications);
        $health = new HealthController($this->db);

        $router = new Router();
        $router->add('GET', '/', [$capsules, 'index']);
        $router->add('GET', '/capsules/new', [$capsules, 'create']);
        $router->add('POST', '/capsules', [$capsules, 'store']);
        $router->add('GET', '/capsules/{id}', [$capsules, 'show']);
        $router->add('GET', '/capsules/{id}/file', [$capsules, 'file']);
        $router->add('POST', '/capsules/{id}/delete', [$capsules, 'delete']);
        $router->add('GET', '/wall', [$wall, 'index']);
        $router->add('GET', '/notifications', [$notifications, 'index']);
        $router->add('GET', '/health', [$health, 'check']);

        // Login pages exist only when authentication is on (otherwise they return 404).
        if ($this->auth->enabled()) {
            $auth = new AuthController(
                $this->view,
                $this->auth,
                $this->session,
                $this->users,
                new RegisterValidator($this->users),
            );
            $router->add('GET', '/login', [$auth, 'loginForm']);
            $router->add('POST', '/login', [$auth, 'login']);
            $router->add('GET', '/register', [$auth, 'registerForm']);
            $router->add('POST', '/register', [$auth, 'register']);
            $router->add('POST', '/logout', [$auth, 'logout']);
        }

        return $router;
    }

    // Absolute path of the upload folder. A relative UPLOAD_DIR starts at the project root.
    private function uploadDir(): string
    {
        $dir = $this->config->getString('UPLOAD_DIR', 'storage/uploads');
        // Absolute paths look like "/var/uploads" (Linux, macOS) or "C:\uploads" (Windows).
        return preg_match('#^([A-Za-z]:)?[\\\\/]#', $dir) ? $dir : $this->rootDir . '/' . $dir;
    }

    // Show views/error.php (403, 404 or 500).
    private function renderError(int $status, ?string $text = null, ?string $details = null): void
    {
        // Throw away anything a half-rendered template already produced.
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $pages = [
            403 => ['Access denied', 'This capsule is still sealed.'],
            404 => ['Page not found', 'The page or capsule does not exist.'],
            500 => ['Something went wrong', 'Please try again later.'],
        ];
        [$heading, $defaultText] = $pages[$status] ?? $pages[500];

        $this->view->render('error', [
            'title' => $heading,
            'code' => $status,
            'heading' => $heading,
            'text' => $text ?? $defaultText,
            'details' => $details, // only filled when APP_DEBUG=true
        ], $status);
    }
}
